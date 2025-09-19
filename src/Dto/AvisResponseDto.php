<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AvisResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'auteurId', type: 'integer', example: 1),
        new OA\Property(property: 'auteurPseudo', type: 'string', example: 'pseudo'),
        new OA\Property(property: 'note', type: 'integer', example: 1, minimum: 1, maximum: 5),
        new OA\Property(property: 'commentaire', type: 'string', example: 'commentaire', maxLength: 255),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente'),
    ])]
class AvisResponseDto
{
}
