<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EmailContactDto',
    type: 'object',
    required: ['nom', 'prenom', 'email', 'subject', 'message'],
    properties: [
        new OA\Property(property: 'nom', type: 'string', example: 'nom'),
        new OA\Property(property: 'prenom', type: 'string', example: 'prenom'),
        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
        new OA\Property(property: 'subject', type: 'string', example: 'subject'),
        new OA\Property(property: 'message', type: 'string', example: 'message'),
    ])]
class EmailContactDto
{
}
