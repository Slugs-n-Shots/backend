<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'Error response',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Whoops! It seems something did not go as planned.'
        ),
    ]
)]
final class ErrorResponseSchema
{
}
