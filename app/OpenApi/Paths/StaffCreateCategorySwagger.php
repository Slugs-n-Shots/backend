<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/categories', operationId: 'staffCreateCategory', summary: 'Create a drink category', tags: ['Staff categories'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkCategoryRequest')), responses: [
    new OA\Response(response: 200, description: 'Created category.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkCategory')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffCreateCategorySwagger
{
}
