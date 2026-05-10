<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/orders/assign/{order_id}', operationId: 'staffAssignOrder', summary: 'Assign an order to current staff user', tags: ['Staff orders'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'order_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Assignment result.', content: new OA\JsonContent(oneOf: [new OA\Schema(type: 'string'), new OA\Schema(ref: '#/components/schemas/Order')])),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
])]
final class StaffAssignOrderSwagger
{
}
