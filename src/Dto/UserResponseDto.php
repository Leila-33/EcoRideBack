<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'user', type: 'string', example: "Nom d'utilisateur"),
        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
    ])]
class UserResponseDto
{
}
