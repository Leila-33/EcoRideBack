<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ErrorResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'errors', type: 'string')])]
class ErrorResponseDto
{
}
