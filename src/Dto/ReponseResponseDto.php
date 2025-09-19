<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ReponseResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'reponse1', type: 'string', example: 'oui', enum: ['oui', 'non']),
        new OA\Property(property: 'reponse2', type: 'string', example: null, enum: ['oui'], nullable: true),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente', enum: ['en attente', 'résolu']),
    ])]
class ReponseResponseDto
{
}
