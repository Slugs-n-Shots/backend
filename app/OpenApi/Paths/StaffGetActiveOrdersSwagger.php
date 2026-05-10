<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/orders/active', operationId: 'staffGetActiveOrders', summary: 'List active orders', tags: ['Staff orders'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Orders.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetActiveOrdersSwagger
{
}
