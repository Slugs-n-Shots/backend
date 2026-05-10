<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drinks', operationId: 'staffListDrinks', summary: 'List drinks', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'with', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'category,units')),
    new OA\Parameter(name: 'nolang', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
], responses: [
    new OA\Response(response: 200, description: 'Drinks.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drink'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffListDrinksSwagger
{
}
