<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserCreateDto',
    type: 'object',
    required: ['prenom', 'nom', 'pseudo', 'dateNaissance', 'email', 'password'],
    properties: [
        new OA\Property(property: 'nom', type: 'string', example: 'nom', maxLength: 50),
        new OA\Property(property: 'prenom', type: 'string', example: 'prenom', maxLength: 50),
        new OA\Property(property: 'pseudo', type: 'string', example: 'pseudo', maxLength: 50),
        new OA\Property(property: 'dateNaissance', type: 'string', format: 'date', example: '1990-01-01'),
        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
        new OA\Property(property: 'password', type: 'string', example: 'motDePasse'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true, example: '0600000000'),
        new OA\Property(property: 'adresse', type: 'string', nullable: true, example: '3 place', maxLength: 255),
    ])]
class UserCreateDto
{
}
