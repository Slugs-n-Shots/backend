<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/tables/current/stats',
    operationId: 'guestCurrentTableStats',
    summary: 'Get owner statistics for the current table session',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Current table payable totals, limit and per-guest consumption.', content: new OA\JsonContent(ref: '#/components/schemas/CurrentTableStatsResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
    ]
)]
final class GuestCurrentTableStatsSwagger
{
}
