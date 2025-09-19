<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CommentaireCreateDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'commentaire', type: 'string', example: 'commentaire'),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente', enum: ['en attente', 'résolu']),
    ])]
class CommentaireCreateDto
{
}
