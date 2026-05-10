<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/refresh',
    operationId: 'guestRefresh',
    summary: 'Refresh guest JWT token',
    tags: ['Guest auth'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Refreshed token.', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestRefreshSwagger
{
}
