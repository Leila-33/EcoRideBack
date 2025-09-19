<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PasswordCreateDto',
    type: 'object',
    required: ['currentPassword', 'password'],
    properties: [
        new OA\Property(property: 'currentPassword', type: 'string', example: 'motDePasse'),
        new OA\Property(property: 'password', type: 'string', example: 'motDePasse123'),
    ])]
class PasswordCreateDto
{
}
