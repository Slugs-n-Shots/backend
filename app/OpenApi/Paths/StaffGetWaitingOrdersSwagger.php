<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/orders/waiting', operationId: 'staffGetWaitingOrders', summary: 'List waiting orders for current staff role', tags: ['Staff orders'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Waiting orders or null.', content: new OA\JsonContent(type: 'array', nullable: true, items: new OA\Items(ref: '#/components/schemas/OrderWithDetails'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetWaitingOrdersSwagger
{
}
