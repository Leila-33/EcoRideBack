<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AvisCreateDto',
    type: 'object',
    required: ['note'],
    properties: [
        new OA\Property(property: 'note', type: 'integer', example: 1, minimum: 1, maximum: 5),
        new OA\Property(property: 'commentaire', type: 'string', example: 'commentaire', maxLength: 255),
        new OA\Property(property: 'chauffeur', type: 'integer', example: 1),
    ])]
class AvisCreateDto
{
}
