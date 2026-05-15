<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/current/spending-limit',
    operationId: 'guestUpdateCurrentTableSpendingLimit',
    summary: 'Update owner spending limit for the current table session',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/OwnerSpendingLimitRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Updated table session limits.', content: new OA\JsonContent(ref: '#/components/schemas/TableSessionLimitResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestTableSpendingLimitSwagger
{
}
