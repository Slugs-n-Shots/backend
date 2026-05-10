<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drinks/{drink}', operationId: 'staffGetDrink', summary: 'Get a drink', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
    new OA\Parameter(name: 'with', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'category,units')),
    new OA\Parameter(name: 'nolang', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
], responses: [
    new OA\Response(response: 200, description: 'Drink.', content: new OA\JsonContent(ref: '#/components/schemas/Drink')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetDrinkSwagger
{
}
