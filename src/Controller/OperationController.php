<?php

namespace App\Controller;

use App\Repository\OperationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/operation', name: 'app_api_operation_')]
class OperationController extends AbstractController
{
    public function __construct(
        private UserRepository $userrepository,
        private EntityManagerInterface $manager,
        private OperationRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/nombreCreditsParJour', name: 'nombreCreditsParJour', methods: ['GET'])]
    #[OA\Get(
        path: '/api/operation/nombreCreditsParJour',
        summary: "Récupère le nombre de crédits gagnés par l'utilisateur courant regroupé par date d'opération.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des dates d'opération avec le nombre de crédits gagnés",
                content : ['application/json' => new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'dateOperation', type: 'string', format: 'date', example: '2025-09-02'),
                            new OA\Property(property: 'total', type: 'string', format: 'decimal', example: '3.00'),
                        ]))])])]
    public function nombreCreditsParJour(Request $request): JsonResponse
    {
        $credits = $this->repository->findByDay();

        return new JsonResponse($credits, Response::HTTP_OK);
    }
}
