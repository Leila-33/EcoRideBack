<?php

namespace App\Controller;

use App\Dto\AvisCreateDto;
use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\UserRepository;
use App\Repository\VoitureRepository;
use App\Utilis\Sanitizer;
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

#[Route('/api/avis', name: 'app_api_avis_')]
class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
        private VoitureRepository $voiturerepository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    #[Route(name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/avis',
        summary: 'Poster un avis',
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'avis à inscrire",
            content: new OA\JsonContent(ref: '#/components/schemas/AvisCreateDto')),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Avis créé avec succès',
                content: new OA\JsonContent(ref: '#/components/schemas/AvisResponseDto')
            ),
            new OA\Response(
                response: 400,
                description: 'Erreur lors de la validation',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')),
            new OA\Response(
                response: 404,
                description: 'Chauffeur non trouvé',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')
            ),
        ])
    ]
    public function new(Request $request, ValidatorInterface $validator, AvisCreateDto $dto): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $chauffeur = $this->userRepository->findOneBy(['id' => $data['chauffeur']]);
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur introuvable'], Response::HTTP_NOT_FOUND);
        }
        $avis = new Avis();
        $avis->setNote($data['note']);
        $avis->setCommentaire(Sanitizer::sanitizeText($data['commentaire']));
        if ('' === trim($avis->getCommentaire())) {
            $avis->setStatut('validé');
        } else {
            $avis->setStatut('en attente');
        }
        $this->getUser()->addAvi($avis);
        $chauffeur->addAvisRecu($avis);
        if ($errorResponse = Validator::validateEntity($avis, $validator)) {
            return $errorResponse;
        }
        $this->manager->persist($avis);
        $this->manager->flush();

        return new JsonResponse($this->toArray($avis), Response::HTTP_CREATED);
    }

    #[Route('/allAvis/{id}', name: 'allAvis', methods: ['GET'])]
    #[OA\Get(
        path: '/api/avis/allAvis/{id}',
        summary: 'Récupère la liste de tous les avis associés au chauffeur identifié par id',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du chauffeur dont on récupère les avis',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste de tous les avis du chauffeur',
                content: ['application/json' => new OA\JsonContent(
                    type: 'array', items: new OA\Items(ref: '#/components/schemas/AvisResponseDto'))]),
            new OA\Response(
                response: 404,
                description: 'Chauffeur non trouvé',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ]
    )]
    public function allAvis(int $id): JsonResponse
    {
        $chauffeur = $this->userRepository->findOneBy(['id' => $id]);
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $avisList = $this->repository->findBy(['chauffeur' => $id]);
        $avisFiltered = array_map(function ($avis) {
            if ('validé' === $avis->getStatut() || $avis->getAuteur()->getId() === $this->getUser()->getId()) {
                return $this->toArray($avis);
            }

            return [
                'id' => $avis->getId(),
                'auteurId' => $avis->getAuteur()->getId(),
                'auteurPseudo' => $avis->getAuteur()->getPseudo(),
                'note' => $avis->getNote(),
                'commentaire' => null,
                'statut' => $avis->getStatut(),
            ];
        }, $avisList);

        return new JsonResponse($avisFiltered, Response::HTTP_OK);
    }

    #[Route('/avisAVerifier', name: 'avisAVerifier', methods: ['GET'])]
    #[OA\Get(
        path: '/api/avis/avisAVerifier',
        summary: "Récupère la liste de tous les avis dont le statut est 'en attente'",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste de tous les avis dont le statut est 'en attente'",
                content: ['application/json' => new OA\JsonContent(
                    type: 'array', items: new OA\Items(ref: '#/components/schemas/AvisResponseDto'))]),
        ]
    )]
    public function avisAVerifier(): JsonResponse
    {
        $avisList = $this->repository->findBy(['statut' => 'en attente']);
        $avisFiltered = array_map(function ($avis) { return $this->toArray($avis); }, $avisList);

        return new JsonResponse($avisFiltered, Response::HTTP_OK);
    }

    #[Route('/validerAvis/{id}', name: 'validerAvis', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/avis/validerAvis/{id}',
        summary: 'Valider un avis',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: "Identifiant de l'avis à valider",
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Avis validé avec succès'),
            new OA\Response(
                response: 404,
                description: 'Avis non trouvé',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ]
    )]
    public function validerAvis(int $id): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);
        if (!$avis) {
            return new JsonResponse(['error' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $avis->setStatut('validé');
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/avis/{id}',
        summary: 'Supprimer un avis',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: "Identifiant de l'avis à supprimer",
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Avis supprimé avec succès.'),
            new OA\Response(
                response: 404,
                description: 'Avis non trouvé',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]
            ),
        ])
    ]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);
        if (!$avis) {
            return new JsonResponse(['error' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $this->manager->remove($avis);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function toArray(Avis $avis)
    {
        return [
            'id' => $avis->getId(),
            'auteurId' => $avis->getAuteur()->getId(),
            'auteurPseudo' => $avis->getAuteur()->getPseudo(),
            'note' => $avis->getNote(),
            'commentaire' => $avis->getCommentaire(),
            'statut' => $avis->getStatut(),
        ];
    }
}
