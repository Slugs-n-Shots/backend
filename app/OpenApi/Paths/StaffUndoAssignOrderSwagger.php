<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/orders/undo-assign/{order_id}', operationId: 'staffUndoAssignOrder', summary: 'Undo current staff order assignment', tags: ['Staff orders'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'order_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Undo assignment result.', content: new OA\JsonContent(type: 'string')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffUndoAssignOrderSwagger
{
}
