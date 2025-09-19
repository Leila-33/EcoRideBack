<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'RoleEntityResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'role', type: 'string', example: 'passager'),
    ])]
class RoleEntityResponseDto
{
}
