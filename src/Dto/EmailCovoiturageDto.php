<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EmailCovoiturageDto',
    type: 'object',
    required: ['subject'],
    properties: [
        new OA\Property(property: 'subject', type: 'string', example: 'subject', enum: ['finTrajet', 'annuler', 'remboursementEchoue', 'confirmationPassager']),
        new OA\Property(property: 'utilisateurs', type: 'array', items: new OA\Items(type: 'object',
            properties : [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'pseudo', type: 'string', example: 'pseudo'),
                new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com')]), nullable : true)])]
class EmailCovoiturageDto
{
}
