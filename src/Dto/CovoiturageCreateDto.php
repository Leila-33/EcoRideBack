<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CovoiturageCreateDto',
    type: 'object',
    required: ['dateDepart', 'heureDepart', 'lieuDepart', 'dateArrivee', 'heureArrivee', 'lieuArrivee', 'nbPlaces'],
    properties: [
        new OA\Property(property: 'dateDepart', type: 'string', format: 'date', example: '2025-09-02'),
        new OA\Property(property: 'heureDepart', type: 'string', example: '16:30'),
        new OA\Property(property: 'lieuDepart', type: 'string', example: 'Bordeaux', maxLength: 50),
        new OA\Property(property: 'dateArrivee', type: 'string', format: 'date', example: '2025-09-02'),
        new OA\Property(property: 'heureArrivee', type: 'string', example: '23:30'),
        new OA\Property(property: 'lieuArrivee', type: 'string', example: 'Paris', maxLength: 50),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente', enum: ['en attente', 'en cours', 'terminé']),
        new OA\Property(property: 'nbPlaces', type: 'integer', example: 3),
        new OA\Property(property: 'prixPersonne', type: 'string', format: 'decimal', example: '10.00'),
    ])]
class CovoiturageCreateDto
{
}
