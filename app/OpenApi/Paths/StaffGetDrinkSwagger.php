<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drinks/{drink}', operationId: 'staffGetDrink', summary: 'Get a drink', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
    new OA\Parameter(name: 'with', in: 'query', required: false, description: 'Comma-separated relations to include. Allowed values: category, units. Defaults to units when omitted.', schema: new OA\Schema(type: 'string', example: 'category,units')),
    new OA\Parameter(name: 'nolang', in: 'query', required: false, description: 'When truthy, returns name_en/name_hu and description_en/description_hu instead of localized name/description.', schema: new OA\Schema(type: 'boolean', example: true)),
    new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'en')),
], responses: [
    new OA\Response(response: 200, description: 'Drink.', content: new OA\JsonContent(ref: '#/components/schemas/Drink')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetDrinkSwagger
{
}
