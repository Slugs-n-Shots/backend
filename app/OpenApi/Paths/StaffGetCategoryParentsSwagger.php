<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/categories/parents',
    operationId: 'staffGetCategoryParents',
    summary: 'List parent drink categories',
    tags: ['Staff categories'],
    parameters: [
        new OA\Parameter(name: 'nolang', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Parent categories.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkCategory'))),
    ]
)]
final class StaffGetCategoryParentsSwagger
{
}
