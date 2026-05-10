<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/categories/{category}',
    operationId: 'staffGetCategory',
    summary: 'Get a drink category',
    tags: ['Staff categories'],
    parameters: [
        new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        new OA\Parameter(name: 'nolang', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Category.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkCategory')),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
final class StaffGetCategorySwagger
{
}
