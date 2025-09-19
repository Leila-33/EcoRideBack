<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'RoleEntityCreateDto',
    type: 'object',
    required: ['role'],
    properties: [
        new OA\Property(property: 'role', type: 'string', example: 'passager', enum: ['chauffeur', 'passager']),
    ])]
class RoleEntityCreateDto
{
}
