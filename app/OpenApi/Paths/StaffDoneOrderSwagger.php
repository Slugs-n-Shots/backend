<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/orders/done/{order_id}', operationId: 'staffDoneOrder', summary: 'Mark an assigned order task as done', tags: ['Staff orders'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'order_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Completion result.', content: new OA\JsonContent(type: 'string')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffDoneOrderSwagger
{
}
