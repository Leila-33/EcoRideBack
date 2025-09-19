<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CommentaireResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'commentaire', type: 'string', example: 'commentaire'),
        new OA\Property(property: 'statut', type: 'string', example: 'en attente', enum: ['en attente', 'résolu'])])]

class CommentaireResponseDto
{
}
