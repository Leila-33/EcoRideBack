<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Repository\CommentaireRepository;
use App\Repository\CovoiturageRepository;
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

#[Route('/api/commentaire', name: 'app_api_commentaire_')]
class CommentaireController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CommentaireRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private CovoiturageRepository $covoiturageRepository,
    ) {
    }

    #[Route('/{id}', name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/commentaire/{id}',
        summary: 'Envoyer un commentaire au support.',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Commentaire à envoyer',
            content: new OA\JsonContent(ref: '#/components/schemas/CommentaireCreateDto')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Commentaire envoyé avec succès',
                content: new OA\JsonContent(ref: '#/components/schemas/CommentaireResponseDto')
            ),
            new OA\Response(response: 400, description: 'Erreur lors de la validation', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
            new OA\Response(response: 404, description: 'Covoiturage introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
            new OA\Response(response: 409, description: 'Commentaire déjà envoyé')]
    )]
    public function new(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $covoiturage = $this->covoiturageRepository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        $commentaireFound = $this->repository->findOneBy(['auteur' => $this->getUser(), 'covoiturage' => $covoiturage]);
        if ($commentaireFound) {
            return new JsonResponse(['error' => 'Commentaire déjà envoyé.'], Response::HTTP_CONFLICT);
        }
        $commentaire = new Commentaire();
        $data = json_decode($request->getContent(), true);
        $commentaire->setCommentaire(Sanitizer::sanitizeText($data['commentaire'] ?? null));
        $commentaire->setStatut('en attente');
        $commentaire->setAuteur($this->getUser());
        $commentaire->setCovoiturage($covoiturage);
        if ($errorResponse = Validator::validateEntity($commentaire, $validator)) {
            return $errorResponse;
        }
        $this->manager->persist($commentaire);
        $this->manager->flush();

        return new JsonResponse(['id' => $commentaire->getId(),
            'commentaire' => $commentaire->getCommentaire(),
            'statut' => $commentaire->getStatut(), ], Response::HTTP_CREATED);
    }
}
