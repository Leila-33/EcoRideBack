<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserMeResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nom', type: 'string', example: 'nom', maxLength: 50),
        new OA\Property(property: 'prenom', type: 'string', example: 'prenom', maxLength: 50),
        new OA\Property(property: 'pseudo', type: 'string', example: 'pseudo', maxLength: 50),
        new OA\Property(property: 'photo', type: 'string', nullable: true, example: 'uploads/photos/photo_689328197.png', maxLength: 255),
        new OA\Property(property: 'dateNaissance', type: 'string', format: 'date', example: '1990-01-01'),
        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true, example: '0600000000'),
        new OA\Property(property: 'adresse', type: 'string', nullable: true, example: '3 place', maxLength: 255),
        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e212f116124a36af14ea0c1c3806eb9378'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
        new OA\Property(property: 'credit', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example:1),
            new OA\Property(property: 'total', type: 'number', format:'float', example:50.0)]),
        new OA\Property(property: 'parametres', type: 'array', items: new OA\Items(type:'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example:1),
            new OA\Property(property: 'propriete', type: 'string', example:'fumeurs'),
            new OA\Property(property: 'valeur', type: 'string', example:'non')]))

    ])]
class UserMeResponseDto
{
}
