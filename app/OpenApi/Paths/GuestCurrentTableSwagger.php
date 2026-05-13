<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/tables/current',
    operationId: 'guestCurrentTable',
    summary: 'Get the current table owned by or joined by the guest',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Current table or null.', content: new OA\JsonContent(ref: '#/components/schemas/CurrentTableResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestCurrentTableSwagger
{
}
