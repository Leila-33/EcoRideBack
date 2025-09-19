<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ReponseEditDto',
    type: 'object',
    required: ['reponse2'],
    properties: [
        new OA\Property(property: 'reponse2', type: 'string', example: 'oui', enum: ['oui']),
    ])]
class ReponseEditDto
{
}
