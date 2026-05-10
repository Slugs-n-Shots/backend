<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/menu-tree',
    operationId: 'getStaffMenuTree',
    summary: 'Get the active drink menu tree for staff',
    tags: ['Staff menu'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Category tree with drinks.', content: new OA\JsonContent(type: 'object')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GetStaffMenuTreeSwagger
{
}
