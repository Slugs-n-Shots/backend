<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Delete(path: '/staff/drink-units/{drink_unit}', operationId: 'staffDeleteDrinkUnit', summary: 'Delete a drink unit', tags: ['Staff drink units'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink_unit', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 204, description: 'Drink unit deleted.'),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffDeleteDrinkUnitSwagger
{
}
