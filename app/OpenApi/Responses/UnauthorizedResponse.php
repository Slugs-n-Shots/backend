<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'Unauthorized',
    description: 'Authentication failed or the authenticated guest may not access this resource.',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
final class UnauthorizedResponse
{
}
