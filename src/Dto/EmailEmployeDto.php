<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EmailEmployeDto',
    type: 'object',
    required: ['email', 'subject', 'message'],
    properties: [
        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
        new OA\Property(property: 'subject', type: 'string', example: 'subject'),
        new OA\Property(property: 'message', type: 'string', example: 'message')])]
class EmailEmployeDto
{
}
