<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/tables/available',
    operationId: 'guestListAvailableTables',
    summary: 'List available tables for guests',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Available tables.', content: new OA\JsonContent(ref: '#/components/schemas/AvailableTablesResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestListAvailableTablesSwagger
{
}
