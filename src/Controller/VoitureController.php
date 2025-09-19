<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Repository\MarqueRepository;
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

#[Route('/api/voiture', name: 'app_api_voiture_')]
class VoitureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private VoitureRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private MarqueRepository $marquerepository,
    ) {
    }

    #[OA\Delete(
        path: '/api/voiture/{id}',
        summary: 'Supprimer une voiture',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant de la voiture à supprimer',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(response: 204, description: 'Voiture supprimée avec succès.'),
            new OA\Response(response: 404, description: 'Voiture introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
        ])
    ]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $voiture = $this->repository->findOneBy(['id' => $id]);
        if (!$voiture) {
            return new JsonResponse(['error' => 'Voiture non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $this->manager->remove($voiture);
        $this->manager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/addVoiture', name: 'addVoiture', methods: ['POST'])]
    #[OA\Post(
        path: '/api/voiture/addVoiture',
        summary: 'Enregistrer une nouvelle voiture',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données de la voiture à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/VoitureCreateDto')]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Voiture enregistrée avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/VoitureResponseDto')]),
            new OA\Response(
                response: 400,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Date de première immatriculation non valide'),
                    ])),
        ]
    )]
    public function addVoiture(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $voiture = $this->serializer->deserialize($request->getContent(), Voiture::class, 'json');
        $voiture->setImmatriculation(Sanitizer::sanitizeText($voiture->getImmatriculation()));
        if (!$voiture->getMarque()) {
            return new JsonResponse(['error' => 'La marque est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }
        $voiture->getMarque()->setLibelle(Sanitizer::sanitizeText($voiture->getMarque()->getLibelle()));
        $voiture->setModele(Sanitizer::sanitizeText($voiture->getModele()));
        $voiture->setCouleur(Sanitizer::sanitizeText($voiture->getCouleur()));
        if ($errorResponse = Validator::validateEntity($voiture, $validator)) {
            return $errorResponse;
        }
        $libelle = $voiture->getMarque()->getLibelle();
        $marque = $this->marquerepository->findOneBy(['libelle' => $libelle]);
        if ($marque) {
            $marque->addVoiture($voiture);
        } else {
            $voiture->getMarque()->addVoiture($voiture);
            $this->manager->persist($voiture->getMarque());
        }
        $this->getUser()->addVoiture($voiture);
        $this->manager->persist($voiture);
        $this->manager->flush();

        return new JsonResponse($this->toArray($voiture), Response::HTTP_CREATED);
    }

    #[Route('/allVoitures', name: 'allVoitures', methods: ['GET'])]
    #[OA\Get(
        path: '/api/voiture/allVoitures',
        summary: "Récupère la liste de toutes les voitures d'un utilisateur",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste de toutes les voitures d'un utilisateur",
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/VoitureResponseDto'))]
            )])]
    public function allVoitures(): JsonResponse
    {
        $voitures = $this->getUser()->getVoitures();
        $filtered = array_map(fn ($voiture) => $this->toArray($voiture), $voitures->toArray());
        return new JsonResponse($filtered, Response::HTTP_OK);
    }

    public function toArray(Voiture $voiture): array
    {
        return [
            'id' => $voiture->getId(),
            'marque' => [
                'id' => $voiture->getMarque()->getId(),
                'libelle' => $voiture->getMarque()->getLibelle(),
            ],
            'modele' => $voiture->getModele(),
            'immatriculation' => $voiture->getImmatriculation(),
            'energie' => $voiture->getEnergie(),
            'couleur' => $voiture->getCouleur(),
            'datePremiereImmatriculation' => $voiture->getDatePremiereImmatriculation()?->format('Y-m-d'),
            'nbPlaces' => $voiture->getNbPlaces()];
    }
}
