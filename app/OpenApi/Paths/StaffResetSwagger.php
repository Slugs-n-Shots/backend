<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/reset',
    operationId: 'staffReset',
    summary: 'Echo password reset query parameters',
    tags: ['Staff auth'],
    responses: [
        new OA\Response(response: 200, description: 'Request query parameters.', content: new OA\JsonContent(type: 'object')),
    ]
)]
final class StaffResetSwagger
{
}
