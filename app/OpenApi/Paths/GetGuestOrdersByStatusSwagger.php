<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/orders/{status}',
    operationId: 'getGuestOrdersByStatus',
    summary: 'Get the authenticated guest orders by status',
    tags: ['Guest orders'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['active'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Guest orders.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderWithDetails'))),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GetGuestOrdersByStatusSwagger
{
}
