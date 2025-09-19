<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ReponseCreateDto',
    type: 'object',
    required: ['reponse1'],
    properties: [
        new OA\Property(property: 'reponse1', type: 'string', example: 'oui', enum: ['oui', 'non']),
    ])]
class ReponseCreateDto
{
}
