<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserListResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nom', type: 'string', example: 'nom', maxLength: 50),
        new OA\Property(property: 'prenom', type: 'string', example: 'prenom', maxLength: 50),
    ])]
class UserListResponseDto
{
}
