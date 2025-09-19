<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'LoginDto',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
        new OA\Property(property: 'password', type: 'string', example: 'Mot de passe'),
    ])]
class LoginDto
{
}
