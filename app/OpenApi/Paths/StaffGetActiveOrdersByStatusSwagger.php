<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/orders/active/{status}', operationId: 'staffGetActiveOrdersByStatus', summary: 'List active orders by status filter', tags: ['Staff orders'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'status', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'active')),
], responses: [
    new OA\Response(response: 200, description: 'Orders.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetActiveOrdersByStatusSwagger
{
}
