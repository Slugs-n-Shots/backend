<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/orders/lastid', operationId: 'staffGetLastOrderId', summary: 'Get the latest order', tags: ['Staff orders'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Latest order.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetLastOrderIdSwagger
{
}
