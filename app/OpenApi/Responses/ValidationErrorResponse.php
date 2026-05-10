<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationError',
    description: 'The given data was invalid.',
    content: new OA\JsonContent(
        type: 'object',
        required: ['message', 'errors'],
        properties: [
            new OA\Property(
                property: 'message',
                type: 'string',
                example: 'The given data was invalid.'
            ),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                ),
                example: ['email' => ['The email field is required.']]
            ),
        ]
    )
)]
final class ValidationErrorResponse
{
}
