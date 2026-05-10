<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drink-units/{drink_unit}', operationId: 'staffGetDrinkUnit', summary: 'Get a drink unit', tags: ['Staff drink units'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink_unit', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Drink unit.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkUnit')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetDrinkUnitSwagger
{
}
