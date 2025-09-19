<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ParametreResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'propriete', type: 'string', example: 'animaux'),
        new OA\Property(property: 'valeur', type: 'string', example: 'non'),
    ])]
class ParametreResponseDto
{
}
