<?php

namespace App\Controller;

use App\Entity\Parametre;
use App\Repository\ParametreRepository;
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

#[Route('/api/parametre', name: 'app_api_parametre_')]
class ParametreController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ParametreRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/parametre',
        summary: 'Enregistrer ou mettre à jour un paramètre',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du paramètre à enregistrer',
            content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ParametreCreateDto')]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Paramètre crée avec succès',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ParametreResponseDto')]),
            new OA\Response(
                response: 200,
                description: 'Paramètre mis à jour',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ParametreResponseDto')]),
            new OA\Response(
                response: 400,
                description: 'Erreur lors de la validation ou nombre maximal de paramètres atteint',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ])
    ]
    public function new(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $parametre = $this->serializer->deserialize($request->getContent(), Parametre::class, 'json');
        $parametre->setPropriete(Sanitizer::sanitizeText($parametre->getPropriete()));
        $parametre->setValeur(Sanitizer::sanitizeText($parametre->getValeur()));
        if ($errorResponse = Validator::validateEntity($parametre, $validator)) {
            return $errorResponse;
        }
        $parametreFound = $this->repository->findOneByUserAndProperty($this->getUser(), $parametre->getPropriete());
        if ($parametreFound) {
            if ($parametre->getValeur() !== $parametreFound->getValeur()) {
                $parametreFound->setValeur($parametre->getValeur());
                $this->manager->flush();
            }
            $statusCode = Response::HTTP_OK;
            $parametre = $parametreFound;
        } else {
            if (count($this->getUser()->getParametres()) >= 10) {
                return new JsonResponse(['error' => 'Le nombre maximal de préférences est de 10.'], Response::HTTP_BAD_REQUEST);
            }
            $this->getUser()->addParametre($parametre);
            $this->manager->persist($parametre);
            $this->manager->flush();
            $statusCode = Response::HTTP_CREATED;
        }

        return new JsonResponse($this->toArray($parametre), $statusCode);
    }

    #[Route(name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/parametre',
        summary: "Récupère les paramètres de l'utilisateur courant.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste de tous les paramètres de l'utilisateur courant",
                content: ['application/json' => new OA\JsonContent(
                    type: 'array',
                    items : new OA\Items(ref: '#/components/schemas/ParametreResponseDto'))]
            ),
        ]
    )]
    public function show(): JsonResponse
    {
        $parametres = $this->repository->findBy(['users' => $this->getUser()]);
        $filtered = array_map(fn (Parametre $parametre) => $this->toArray($parametre), $parametres);

        return new JsonResponse($filtered, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/parametre/{id}',
        summary: "Supprimer le lien entre le paramètre et l'utilisateur. Si le paramètre n'est plus associé à personne, suppression du paramètre.",
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant du paramètre à supprimer',
            schema: new OA\Schema(type: 'integer', example: 1)
        )],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Paramètre supprimé avec succès.',
            ),
            new OA\Response(
                response: 404,
                description: "Paramètre introuvable ou non associé à l'utilisateur",
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ])
    ]
    public function delete(int $id): JsonResponse
    {
        $parametre = $this->repository->findOneBy(['id' => $id]);
        if (!$parametre) {
            return new JsonResponse(['error' => 'Paramètre introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if (!$parametre->getUsers()->contains($this->getUser())) {
            return new JsonResponse(['error' => "Ce paramètre n'est pas associé à l'utilisateur"], Response::HTTP_NOT_FOUND);
        }
        $parametre->removeUser($this->getUser());
        if ($parametre->getUsers()->isEmpty()) {
            $this->manager->remove($parametre);
        }
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(Parametre $parametre)
    {
        return [
            'id' => $parametre->getId(),
            'propriete' => $parametre->getPropriete(),
            'valeur' => $parametre->getValeur(),
        ];
    }
}
