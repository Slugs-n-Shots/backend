<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Put(path: '/staff/categories/{category}', operationId: 'staffUpdateCategory', summary: 'Update a drink category', tags: ['Staff categories'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkCategoryRequest')), responses: [
    new OA\Response(response: 200, description: 'Updated category.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkCategory')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffUpdateCategorySwagger
{
}
