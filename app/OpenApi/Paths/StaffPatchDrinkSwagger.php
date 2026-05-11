<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Patch(path: '/staff/drinks/{drink}', operationId: 'staffPatchDrink', summary: 'Partially update a drink', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
    new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'en')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkUpdateRequest')), responses: [
    new OA\Response(response: 200, description: 'Updated drink.', content: new OA\JsonContent(ref: '#/components/schemas/Drink')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffPatchDrinkSwagger
{
}
