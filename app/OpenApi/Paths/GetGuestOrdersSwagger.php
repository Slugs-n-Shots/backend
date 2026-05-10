<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/orders',
    operationId: 'getGuestOrders',
    summary: 'Get the authenticated guest orders',
    tags: ['Guest orders'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Guest orders.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderWithDetails'))),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GetGuestOrdersSwagger
{
}
