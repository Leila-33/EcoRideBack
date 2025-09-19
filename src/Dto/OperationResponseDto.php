<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'OperationResponseDto',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'dateOperation', type: 'string', format: 'date', example: '2025-09-02'),
        new OA\Property(property: 'operation', type: 'string', format: 'decimal', example: 10.00)])]
class OperationResponseDto
{
}
