<?php

namespace App\Controller;

use App\Entity\Credit;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utilis\Sanitizer;
use App\Utilis\Validator;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $repository,
    ) {
    }

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    #[OA\Post(
        path: '/api/registration',
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserCreateDto')]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserResponseDto')]),
            new OA\Response(response: 400, description: 'Erreur lors de la validation ou données invalide', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ]
    )]
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $photoB64 = $data['photo'] ?? null;
        if (!empty($photoB64)) {
            $decoded = base64_decode($photoB64, true);
            if (false === $decoded) {
                return new JsonResponse(['error' => 'Image non valide'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($decoded) > 2 * 1024 * 1024) {
                return new JsonResponse(['error' => 'Image trop volumineuse'], Response::HTTP_BAD_REQUEST);
            }
            $imgInfo = @getimagesizefromstring($decoded);
            if (!$imgInfo || !in_array($imgInfo['mime'], ['image/jpeg', 'image/png'])) {
                return new JsonResponse(['error' => 'Type d\'image interdit'], Response::HTTP_BAD_REQUEST);
            }
            $extension = 'image/png' === $imgInfo['mime'] ? 'png' : 'jpg';
            $filename = uniqid('photo_', true).'.'.$extension;
            $uploadDir = $this->getParameter('kernel.project_dir').'/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $image = \imagecreatefromstring($decoded);
            if (!$image) {
                return new JsonResponse(['error' => 'Erreur lors du traitement de l\'image.'], Response::HTTP_BAD_REQUEST);
            }
            if ('jpg' === $extension) {
                imagejpeg($image, $uploadDir.$filename, 90);
            } else {
                imagepng($image, $uploadDir.$filename, 6);
            }
            imagedestroy($image);
            $user->setPhoto('uploads/photos/'.$filename);
        }
        $user->setPseudo(Sanitizer::sanitizeText($data['pseudo']));
        $user->setNom(Sanitizer::sanitizeText($data['nom']));
        $user->setPrenom(Sanitizer::sanitizeText($data['prenom']));
        if (!empty($data['dateNaissance'])) {
            $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        }
        $user->setEmail(Sanitizer::sanitizeText($data['email']));
        $user->setTelephone($data['telephone'] ?? '');
        $user->setAdresse(Sanitizer::sanitizeText($data['adresse'] ?? ''));
        if (isset($data['roles']) && is_array($data['roles'])){ 
            $user->setRoles($data['roles']);
        }
        $user->setPassword($data['password']);
        if ($errorResponse = Validator::validateEntity($user, $validator)) {
            return $errorResponse;
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $credit = new Credit();
        $credit->setTotal(20);
        $credit->setUser($user);
        $this->manager->persist($credit);
        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: 'Connecter un utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de l’utilisateur pour se connecter',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/LoginDto')]),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserResponseDto')]),
            new OA\Response(
                response: 401,
                description: 'Identifiants manquants ou incorrects',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Identifiants manquants ou incorrects'),
                    ])),
        ]
    )]
    public function login(#[CurrentUser] ?User $user, Request $request): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['error' => 'Identifiants manquants ou incorrects'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ], Response::HTTP_OK);
    }

    #[Route('/account/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/account/me',
        summary: "Récupère les informations de l'utilisateur connecté.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Informations de l'utilisateur connecté",
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserMeResponseDto')]),
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        return new JsonResponse($this->toArray($user), Response::HTTP_OK);
    }

    #[Route('/personnes', name: 'personnes', methods: ['GET'])]
    #[OA\Get(
        path: '/api/personnes',
        summary: 'Récupère la liste de tous les utilisateurs',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs',
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/UserListResponseDto'))])]
    ) ]
    public function personnes(): JsonResponse
    {
        $users = $this->repository->findAll();
        $filtered = array_map(function ($user) {
            return ['id' => $user->getId(), 'nom' => $user->getNom(), 'prenom' => $user->getPrenom()];
        }, $users);

        return new JsonResponse($filtered, Response::HTTP_OK);
    }

    #[Route('/account/edit', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/account/edit',
        summary: "Modifier les informations personnelles de l'utilisateur connecté",
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données à mettre à jour',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserEditDto')]),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil mis à jour avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/UserMeResponseDto')]),
            new OA\Response(response: 400, description: 'Erreur lors de la validation ou données invalides', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')])]
    )]
    public function edit(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $photoB64 = $data['photo'] ?? null;
        $deletePhoto = $data['delete_photo'] ?? false;
        if ($deletePhoto && $user->getPhoto()) {
            $this->deletePhotoFile($user);
            $user->setPhoto(null);
        }
        if (!empty($photoB64)) {
            $decoded = base64_decode($photoB64, true);
            if (false === $decoded) {
                return new JsonResponse(['error' => 'Image non valide'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($decoded) > 2 * 1024 * 1024) {
                return new JsonResponse(['error' => 'Image trop volumineuse'], Response::HTTP_BAD_REQUEST);
            }
            $imgInfo = @getimagesizefromstring($decoded);
            if (!$imgInfo || !in_array($imgInfo['mime'], ['image/jpeg', 'image/png'])) {
                return new JsonResponse(['error' => 'Type d\'image interdit'], Response::HTTP_BAD_REQUEST);
            }
            $extension = 'image/png' === $imgInfo['mime'] ? 'png' : 'jpg';
            $filename = uniqid('photo_', true).'.'.$extension;
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $image = \imagecreatefromstring($decoded);
            if (!$image) {
                return new JsonResponse(['error' => 'Erreur lors du traitement de l\'image.'], Response::HTTP_BAD_REQUEST);
            }
            if ('jpg' === $extension) {
                imagejpeg($image, $uploadDir.$filename, 90);
            } else {
                imagepng($image, $uploadDir.$filename, 6);
            }
            imagedestroy($image);
            $this->deletePhotoFile($user);
            $user->setPhoto('uploads/photos/'.$filename);
        }
        if (array_key_exists('nom', $data)) {
            $user->setNom(Sanitizer::sanitizeText($data['nom']));
        }
        if (array_key_exists('prenom', $data)) {
            $user->setPrenom(Sanitizer::sanitizeText($data['prenom']));
        }
        if (!empty($data['dateNaissance'])) {
            try {
                $user->setDateNaissance(new \DateTime($data['dateNaissance']));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Date de naissance invalide'], Response: HTTP_BAD_REQUEST);
            }
        }
        if (array_key_exists('telephone', $data)) {
            $user->setTelephone($data['telephone']);
        }
        if (array_key_exists('adresse', $data)) {
            $user->setAdresse(Sanitizer::sanitizeText($data['adresse']));
        }
        if ($errorResponse = Validator::validateEntity($user, $validator)) {
            return $errorResponse;
        }
        $this->manager->flush();
        return new JsonResponse($this->toArray($user), Response::HTTP_OK);
    }

    #[Route('/editPassword', name: 'editPassword', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/editPassword',
        summary: "Modifier le mot de passe de l'utilisateur connecté",
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Mot de passe',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/PasswordCreateDto')]),
        responses: [
            new OA\Response(response: 204, description: 'Mot de passe changé avec succès'),
            new OA\Response(response: 400, description: 'Erreur lors de la validation ou mot de passe manquant ou inchangé', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ]
    )]
    public function editPassword(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        if (empty($data['password']) || empty($data['currentPassword'])) {
            return new JsonResponse(['error' => 'Mot de passe manquant'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return new JsonResponse(['error' => 'Mot de passe incorrect'], Response::HTTP_FORBIDDEN);
        }
        if ($this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Mot de passe inchangé'], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($data['password']);
        if ($errorResponse = Validator::validateEntity($user, $validator)) {
            return $errorResponse;
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // Supprimer la photo d'un utilisateur
    private function deletePhotoFile(User $user): void
    {
        $photo = $user->getPhoto();
        if ($photo) {
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/photos/';
            $oldPhotoPath = $uploadDir.basename($photo);
            if (file_exists($oldPhotoPath)) {
                unlink($oldPhotoPath);
            }
        }
    }

    #[Route('/{id}', name: 'delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/{id}',
        summary: 'Supprimer un utilisateur',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: "Identifiant de l'utilisateur à supprimer",
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(response: 204, description: 'Utilisateur supprimé avec succès'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
        ])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->repository->findOneBy(['id' => $id]);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }
        $this->deletePhotoFile($user);
        $this->manager->remove($user);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'pseudo' => $user->getPseudo(),
            'photo' => $user->getPhoto(),
            'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
            'parametres' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'propriete' => $p->getPropriete(),
                'valeur' => $p->getValeur(),
            ], $user->getParametres()->toArray()),
            'credit' =>  [
                'id' => $user->getCredit()->getId(),
                'total' => $user->getCredit()->getTotal()
            ]];
    }
}
