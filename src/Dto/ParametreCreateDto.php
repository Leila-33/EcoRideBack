<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ParametreCreateDto',
    type: 'object',
    required: ['propriete', 'valeur'],
    properties: [
        new OA\Property(property: 'propriete', type: 'string', example: 'animaux'),
        new OA\Property(property: 'valeur', type: 'string', example: 'non')])]
class ParametreCreateDto
{
}
