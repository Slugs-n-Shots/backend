<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/refresh',
    operationId: 'staffRefresh',
    summary: 'Refresh staff JWT token',
    tags: ['Staff auth'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Refreshed token.', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class StaffRefreshSwagger
{
}
