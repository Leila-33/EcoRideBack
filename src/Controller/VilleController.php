<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ville', name: 'app_api_ville_')]
class VilleController extends AbstractController
{
    public function __construct(DocumentManager $dm,
        private VilleRepository $villeRepository)
    {
        $this->dm = $dm;
    }

    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    #[OA\Get(
        path: '/api/ville/autocomplete',
        summary: 'Cherche des villes par mot-clé (autocomplétion).',
        parameters: [
            new OA\Parameter(name: 'mot', in: 'query', required: true, description: 'Texte à rechercher',
                schema: new OA\Schema(type: 'string', example: 'li')
            )],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des villes correspondant au mot saisi',
                content: ['application/json' => new OA\JsonContent(type: 'array', items: new OA\Items(type: 'string', example: 'Lille'))]),
            new OA\Response(
                response: 400,
                description: 'Mot vide',
                content: ['application/json' => new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Mot vide', minLength: 1),
                    ])]),
        ])]
    public function autocomplete(Request $request): JsonResponse
    {
        $mot = $request->query->get('mot');
        if (!$mot || '' === trim($mot)) {
            return $this->json(['error' => 'Mot vide'], Response::HTTP_BAD_REQUEST);
        }
        $results = $this->villeRepository->autocomplete($mot);

        return $this->json($results);
    }
}
