<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserEditDto',
    type: 'object',
    required: ['prenom', 'nom', 'dateNaissance'],
    properties: [
        new OA\Property(property: 'nom', type: 'string', example: 'nom', maxLength: 50),
        new OA\Property(property: 'prenom', type: 'string', example: 'prenom', maxLength: 50),
        new OA\Property(property: 'dateNaissance', type: 'string', format: 'date', example: '1990-01-01'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true, example: '0600000000'),
        new OA\Property(property: 'adresse', type: 'string', nullable: true, example: '3 place', maxLength: 255),
        new OA\Property(property: 'photo', type: 'string', nullable: true, example: 'Photo encodée en base 64'),
        new OA\Property(property: 'delete_photo', type: 'boolean', nullable: true, example: false),
    ])]
class UserEditDto
{
}
