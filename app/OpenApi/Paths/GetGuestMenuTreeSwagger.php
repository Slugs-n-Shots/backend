<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/menu-tree',
    operationId: 'getGuestMenuTree',
    summary: 'Get the active drink menu grouped by categories',
    tags: ['Guest menu'],
    responses: [
        new OA\Response(response: 200, description: 'Category tree with drinks.', content: new OA\JsonContent(type: 'object')),
    ]
)]
final class GetGuestMenuTreeSwagger
{
}
