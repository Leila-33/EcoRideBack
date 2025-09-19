<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ErrorNotFoundResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'errors', type: 'string', example: 'Ressource non trouvée')])]
class ErrorNotFoundResponseDto
{
}
