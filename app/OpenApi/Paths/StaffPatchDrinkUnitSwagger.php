<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Patch(path: '/staff/drink-units/{drink_unit}', operationId: 'staffPatchDrinkUnit', summary: 'Partially update a drink unit', tags: ['Staff drink units'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink_unit', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkUnitRequest')), responses: [
    new OA\Response(response: 200, description: 'Updated drink unit.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkUnit')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffPatchDrinkUnitSwagger
{
}
