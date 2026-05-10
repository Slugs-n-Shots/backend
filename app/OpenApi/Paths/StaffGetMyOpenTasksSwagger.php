<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/orders/my-tasks', operationId: 'staffGetMyOpenTasks', summary: 'List current staff open tasks', tags: ['Staff orders'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Open tasks.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderWithDetails'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetMyOpenTasksSwagger
{
}
