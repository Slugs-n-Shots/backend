<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/table-sessions/{tableSession}/spending-limit',
    operationId: 'staffUpdateTableSessionSpendingLimit',
    summary: 'Override staff spending limit for a table session',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'tableSession', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StaffSpendingLimitOverrideRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Updated table session limits.', content: new OA\JsonContent(ref: '#/components/schemas/TableSessionLimitResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class StaffTableSessionSpendingLimitSwagger
{
}
