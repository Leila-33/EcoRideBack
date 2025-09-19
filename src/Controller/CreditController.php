<?php

namespace App\Controller;

use App\Entity\Operation;
use App\Repository\CreditRepository;
use App\Repository\UserRepository;
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

#[Route('/api/credit', name: 'app_api_credit_')]
class CreditController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $manager,
        private CreditRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/nombreCreditsTotal', name: 'nombreCreditsTotal', methods: ['GET'])]
    #[OA\Get(
        path: '/api/credit/nombreCreditsTotal',
        summary: "Récupère le nombre de crédit total détenu par l'utilisateur courant.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Nombre de crédit total détenu par l'utilisateur courant",
                content: ['application/json' => new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'creditTotal', type: 'string', format: 'decimal', example: 30.00),
                    ])])])
    ]
    public function nombreCreditsTotal(Request $request): JsonResponse
    {
        $credits = $this->getUser()->getCredit()->getTotal();

        return new JsonResponse(['creditTotal' => $credits], Response::HTTP_OK);
    }

    #[Route('/payer/{id}/{motif}', name: 'payer', methods: ['POST'])]
    #[OA\Post(
        path: '/api/credit/payer/{id}/{motif}',
        summary: 'Effectue le paiement du participant à la plateforme ou de la plateforme au participant/chauffeur.',
        parameters: [new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identifiant de la personne qui effectue ou qui reçoit le paiement',
            schema: new OA\Schema(type: 'integer', example: 1)
        ),
            new OA\Parameter(
                name: 'motif',
                in: 'path',
                required: true,
                description: 'Motif du paiement',
                schema: new OA\Schema(type: 'string', example: 'achat', enum: ['payerChauffeur', 'achat', 'remboursement'])
            )],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données du paiement à enregistrer',
            content: ['application/json' => new OA\JsonContent(
                type: 'object',
                required: ['dateOperation', 'montant'],
                properties: [
                    new OA\Property(property: 'dateOperation', type: 'string', format: 'date', example: '2025-09-02'),
                    new OA\Property(property: 'montant', type: 'string', format: 'decimal', example: 10.00),
                ])]),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Paiement effectué avec succès',
                content: ['application/json' => new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'creditTotal', type: 'string', format: 'decimal', example: '30.00'),
                    ])]),
            new OA\Response(
                response: 404,
                description: 'Personne introuvable',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorNotFoundResponseDto')]),
            new OA\Response(
                response: 400,
                description: 'Motif invalide ou erreur lors de la validation',
                content: ['application/json' => new OA\JsonContent(ref: '#/components/schemas/ErrorResponseDto')]),
        ])]
    public function payer(Request $request, int $id, string $motif, ValidatorInterface $validator): JsonResponse
    {
        $admin = $this->userRepository->findByRole('ROLE_ADMIN');
        $personne = $this->userRepository->find($id);
        if (!$admin || !$personne) {
            return new JsonResponse(['error' => 'Personne introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $operation1 = $this->serializer->deserialize($request->getContent(), Operation::class, 'json');
        if ($errorResponse = Validator::validateEntity($operation1, $validator)) {
            return $errorResponse;
        }

        $operation2 = new Operation();
        $operation2->setDateOperation($operation1->getDateOperation());
        $montant = $operation1->getMontant();
        if ('payerChauffeur' == $motif) {
            $frais = 2;
            $personne1 = $admin;
            $personne2 = $personne;
            $operation1->setMontant(-$montant + $frais);
            $operation2->setMontant($montant - $frais);
        } elseif ('achat' == $motif) {
            $personne1 = $personne;
            $personne2 = $admin;
            $operation1->setMontant(-$montant);
            $operation2->setMontant($montant);
        } elseif ('remboursement' == $motif) {
            $personne1 = $admin;
            $personne2 = $personne;
            $operation1->setMontant(-$montant);
            $operation2->setMontant($montant);
        } else {
            return new JsonResponse(['error' => 'Motif non valide.'], Response::HTTP_BAD_REQUEST);
        }
        $personne1->addOperation($operation1);
        $personne1->getCredit()->addCredit($operation1->getMontant());
        $personne2->addOperation($operation2);
        $personne2->getCredit()->addCredit($operation2->getMontant());
        $this->manager->persist($operation1);
        $this->manager->persist($operation2);
        $this->manager->flush();

        return new JsonResponse(['creditTotal' => $this->getUser()->getCredit()->getTotal()], Response::HTTP_CREATED);
    }
}
