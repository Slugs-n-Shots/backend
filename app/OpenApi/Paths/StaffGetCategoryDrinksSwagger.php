<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/categories/{category}/drinks', operationId: 'staffGetCategoryDrinks', summary: 'List drinks in a category', tags: ['Staff categories'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Category drinks.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drink'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetCategoryDrinksSwagger
{
}
