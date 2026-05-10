<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'NotFound',
    description: 'Resource not found.',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
final class NotFoundResponse
{
}
