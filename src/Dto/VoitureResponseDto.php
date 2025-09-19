<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'VoitureResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'marque', type: 'object', required: ['libelle'], properties: [
            new OA\Property(property: 'libelle', type: 'string', example: 'voiture', maxLength: 50)]),
        new OA\Property(property: 'modele', type: 'string', example: 'modele', maxLength: 50),
        new OA\Property(property: 'immatriculation', type: 'string', example: 'AB-123-CD', maxLength: 50),
        new OA\Property(property: 'energie', type: 'string', example: 'essence', enum: ['essence', 'electricite']),
        new OA\Property(property: 'couleur', type: 'string', example: 'blanche', maxLength: 50),
        new OA\Property(property: 'datePremiereImmatriculation', type: 'string', example: '1990-01-01'),
        new OA\Property(property: 'nbPlaces', type: 'integer', example: 1),
    ])]
class VoitureResponseDto
{
}
