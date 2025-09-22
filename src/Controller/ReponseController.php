<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Repository\CovoiturageRepository;
use App\Repository\ReponseRepository;
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

#[Route('/api/reponse', name: 'app_api_reponse_')]
class ReponseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ReponseRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private CovoiturageRepository $covoituragerepository,
    ) {
    }

    #[Route('/setReponse1/{id}', name: 'setReponse1', methods: ['POST'])]
    #[OA\Post(
        path: '/api/reponse/setReponse1/{id}',
        summary: "Répondre à la question de fin de trajet d'un covoiturage.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Réponse à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ReponseCreateDto')]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Réponse enregistrée avec succès',
                headers: [new OA\Header(
                    header: 'Location',
                    description: 'Url de la ressource créée',
                    schema: new OA\Schema(type: 'string', format: 'uri')
                )],
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ReponseResponseDto')]),
            new OA\Response(response: 404, description: 'Covoiturage introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')),
            new OA\Response(response: 409, description: 'Réponse déjà enregistrée'),
            new OA\Response(response: 400, description: 'Erreur lors de la validation', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')])])]
    public function setReponse1(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $covoiturage = $this->covoituragerepository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        $reponse = $this->repository->findOneBy(['user' => $this->getUser(), 'covoiturage' => $id]);
        if ($reponse) {
            return new JsonResponse(['error' => 'Réponse déjà donnée.'], Response::HTTP_CONFLICT);
        }
        $reponse = new Reponse();
        $data = json_decode($request->getContent(), true);
        $reponse->setReponse1($data['reponse1'] ?? null);
        if ('non' === $data['reponse1']) {
            $reponse->setStatut('en attente');
        }
        if ($errorResponse = Validator::validateEntity($reponse, $validator)) {
            return $errorResponse;
        }
        $this->getUser()->addReponse($reponse);
        $covoiturage->addReponse($reponse);
        $this->manager->persist($reponse);
        $this->manager->flush();
        $location = $this->generateUrl('app_api_reponse_show', ['id' => $reponse->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($this->toArray2($reponse), Response::HTTP_CREATED, ['Location' => $location]);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reponse/show/{id}',
        summary: "Récupère la réponse de l'utilisateur courant pour un covoiturage donné.",
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Identifiant du covoiturage',
                schema: new OA\Schema(type: 'integer', example: 1)
            )],
        responses: [
            new OA\Response(
                response: 200,
                description: "Réponse de l'utilisateur courant au covoiturage",
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ReponseResponseDto')]),
            new OA\Response(
                response: 404,
                description: 'Réponse non trouvée',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')
            ),
        ])
    ]
    public function reponse(int $id): JsonResponse
    {
        $reponse = $this->repository->findOneBy(['user' => $this->getUser(), 'covoiturage' => $id]);
        if (!$reponse) {
            return new JsonResponse(['error' => 'Réponse non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray2($reponse), Response::HTTP_OK);
    }

    #[Route('/reponsesNon', name: 'reponseNon', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reponse/reponsesNon',
        summary: 'Récupère les réponses négatives à la question de fin de trajet.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste de toutes les réponses négatives à la question de fin de trajet',
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ReponseResponseDto'))]
            )])]
    public function reponsesNon(): JsonResponse
    {
        $reponses = $this->repository->findReponsesNon();
        $reponses = array_filter($reponses);
        $filtered = array_map(function ($reponse) {return $this->toArray($reponse); }, $reponses);

        return new JsonResponse($filtered, Response::HTTP_OK);
    }

    #[Route('/editReponse/{id}', name: 'editReponse', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/reponse/editReponse/{id}',
        summary: "Indiquer si l'utilisateur courant a posté un avis ou un commentaire.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Réponse à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ReponseEditDto')]),

        responses: [
            new OA\Response(
                response: 200,
                description: 'Réponse enregistrée avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ReponseResponseDto')]),
            new OA\Response(response: 404, description: 'Covoiturage ou réponse introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
            new OA\Response(response: 400, description: 'Erreur lors de la validation ou réponse2 manquant', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')])])]
    public function editReponse(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $covoiturage = $this->covoituragerepository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        $reponse = $this->repository->findOneBy(['user' => $this->getUser(), 'covoiturage' => $covoiturage]);
        if (!$reponse) {
            return new JsonResponse(['error' => 'Reponse introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (!isset($data['reponse2'])) {
            return new JsonResponse(['error' => 'réponse2 manquante.'], Response::HTTP_BAD_REQUEST);
        }
        $reponse->setReponse2($data['reponse2']);
        if ($errorResponse = Validator::validateEntity($reponse, $validator)) {
            return $errorResponse;
        }
        $this->manager->flush();

        return new JsonResponse($this->toArray2($reponse), Response::HTTP_OK);
    }

        #[Route('/setStatutResolu/{id}', name: 'setStatutResolu', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/reponse/setStatutResolu/{id}',
        summary: "Marquer la réponse comme résolue.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant de la réponse',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],

        responses: [
            new OA\Response(response: 204, description: 'Statut changé avec succès'),
            new OA\Response(response: 404, description: 'Réponse introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')])])]
    public function setStatutResolu(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $reponse = $this->repository->findOneBy(['id' => $id]);
        if (!$reponse) {
            return new JsonResponse(['error' => 'Réponse introuvable'], Response::HTTP_NOT_FOUND);
        }
        $reponse->setStatut('résolu');
        if ($errorResponse = Validator::validateEntity($reponse, $validator)) {
            return $errorResponse;
        }
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/getNbReponses/{id}', name: 'getNbReponses', methods: ['GET'])]
    #[OA\Get(
        path: '/api/reponse/getNbReponses/{id}',
        summary: 'Récupère le nombre de réponses liés à un covoiturage donné.',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du covoiturage',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nombre de réponses liés à un covoiturage donné',
                content: ['application/json' => new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'nbReponses', type: 'integer', example: 1),
                        ]))]
            ),
            new OA\Response(response: 404, description: 'Covoiturage introuvable', content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')])])]
    public function getNbReponses(int $id): JsonResponse
    {
        $covoiturage = $this->covoituragerepository->findOneBy(['id' => $id]);
        if (!$covoiturage) {
            return new JsonResponse(['error' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        $nbReponses = $this->repository->countByCovoiturages($id);

        return new JsonResponse(['nbReponses' => $nbReponses], Response::HTTP_OK);
    }

    public function toArray(Reponse $reponse)
    {
        $covoiturage = $reponse->getCovoiturage();
        $commentaire = $covoiturage->getCommentaires()->filter(function ($c) use ($reponse) {
            return $c->getAuteur() === $reponse->getUser();
        })->first();

        return [
            'id' => $reponse->getId(),
            'reponse1' => $reponse->getReponse1(),
            'reponse2' => $reponse->getReponse2(),
            'statut' => $reponse->getStatut(),
            'user' => [
                'email' => $reponse->getUser()->getEmail(),
            ],
            'covoiturage' => [
                'id' => $covoiturage->getId(),
                'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
                'heureDepart' => $covoiturage->getHeureDepart(),
                'lieuDepart' => $covoiturage->getLieuDepart(),
                'dateArrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
                'heureArrivee' => $covoiturage->getHeureArrivee(),
                'lieuArrivee' => $covoiturage->getLieuArrivee(),
                'prixPersonne' => $covoiturage->getPrixPersonne(),
                'chauffeur' => [
                    'id' => $covoiturage->getVoiture()->getUser()->getId(),
                    'email' => $covoiturage->getVoiture()->getUser()->getEmail()],
            ],
            'commentaire' => $commentaire ? [
                'id' => $commentaire->getId(),
                'commentaire' => $commentaire->getCommentaire()]
             : null];
    }

    public function toArray2(Reponse $reponse)
    {
        return [
            'id' => $reponse->getId(),
            'reponse1' => $reponse->getReponse1(),
            'reponse2' => $reponse->getReponse2(),
            'statut' => $reponse->getStatut(),
        ];
    }
}
