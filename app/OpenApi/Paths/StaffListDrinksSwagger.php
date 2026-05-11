<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drinks', operationId: 'staffListDrinks', summary: 'List drinks', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'with', in: 'query', required: false, description: 'Comma-separated additional relations to include. Allowed values: category, units. The units relation is always included.', schema: new OA\Schema(type: 'string', example: 'category,units')),
    new OA\Parameter(name: 'nolang', in: 'query', required: false, description: 'When truthy, returns name_en/name_hu and description_en/description_hu instead of localized name/description.', schema: new OA\Schema(type: 'boolean', example: true)),
    new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'en')),
], responses: [
    new OA\Response(response: 200, description: 'Drinks.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drink'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffListDrinksSwagger
{
}
