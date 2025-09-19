<?php

namespace App\Controller;

use App\Entity\RoleEntity;
use App\Repository\RoleEntityRepository;
use App\Utilis\Validator;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/roleEntity', name: 'app_api_roleEntity_')]
class RoleEntityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RoleEntityRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/roleEntity',
        summary: "Ajoute un rôle à l'utilisateur courant.",
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Rôle à ajouter',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/RoleEntityCreateDto')]),
        responses: [
            new OA\Response(
                response: 201,
                description: "Rôle crée ou rôle déjà existant associé à l'utilisateur avec succès",
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/RoleEntityResponseDto')]),
            new OA\Response(response: 409, description: "L'utilisateur possède déjà ce rôle"),
            new OA\Response(response: 400, description: 'Erreur lors de la validation ou pas de voiture enregistrée', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ])]
    public function new(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $roleEntity = $this->serializer->deserialize($request->getContent(), RoleEntity::class, 'json');
        if ($errorResponse = Validator::validateEntity($roleEntity, $validator)) {
            return $errorResponse;
        }
        $libelle = $roleEntity->getLibelle();
        if ('chauffeur' === $libelle && $this->getUser()->getVoitures()->isEmpty()) {
            return $this->json(['error' => "Vous n'avez pas de voiture enregistrée."], Response::HTTP_BAD_REQUEST);
        }
        $roleEntityFound = $this->repository->findOneBy(['libelle' => $libelle]);
        if ($roleEntityFound) {
            if ($roleEntityFound->getUsers()->contains($this->getUser())) {
                return new JsonResponse(['error' => "L'utilisateur possède déjà ce rôle"], Response::HTTP_CONFLICT);
            }
            $roleEntityFound->addUser($this->getUser());
            $roleEntity = $roleEntityFound;
        } else {
            $roleEntity->addUser($this->getUser());
            $this->manager->persist($roleEntity);
        }
        $this->manager->flush();

        return new JsonResponse(['id' => $roleEntity->getId(), 'libelle' => $roleEntity->getLibelle()], Response::HTTP_CREATED);
    }

    #[Route(name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/roleEntity',
        summary: "Récupère les rôles de l'utilisateur courant.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Rôles de l'utilisateur courant",
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/RoleEntityCreateDto'))]
            )])]
    public function show(): JsonResponse
    {
        $roleEntities = $this->getUser()->getRoleEntities();
        $filtered = array_map(fn ($role) => [
            'id' => $role->getId(),
            'libelle' => $role->getLibelle(),
        ], $roleEntities->toArray());

        return new JsonResponse($filtered, Response::HTTP_OK);
    }

    #[Route('/{libelle}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/roleEntity/{libelle}',
        summary: "Supprimer un rôle chez l'utilisateur courant.",
        parameters: [new OA\Parameter(
            name: 'libelle',
            in: 'path',
            required: true,
            description: "Libellé du rôle à retirer de l'utilisateur",
            schema: new OA\Schema(type: 'string', example: 'passager')
        )],
        responses: [
            new OA\Response(response: 204, description: 'Rôle supprimé avec succès.'),
            new OA\Response(response: 404, description: 'Rôle introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
            new OA\Response(response: 409, description: "L'utilisateur ne possède pas ce rôle"),
        ]),
    ]
    public function delete(string $libelle): JsonResponse
    {
        $roleEntity = $this->repository->findOneBy(['libelle' => $libelle]);
        if (!$roleEntity) {
            return new JsonResponse(['error' => 'Rôle introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (!$roleEntity->getUsers()->contains($this->getUser())) {
            return new JsonResponse(['error' => "L'utilisateur ne possède pas ce rôle"], Response::HTTP_CONFLICT);
        }
        $roleEntity->removeUser($this->getUser());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
