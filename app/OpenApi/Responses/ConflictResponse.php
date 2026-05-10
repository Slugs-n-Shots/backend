<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'Conflict',
    description: 'The request conflicts with the current resource state.',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
final class ConflictResponse
{
}
