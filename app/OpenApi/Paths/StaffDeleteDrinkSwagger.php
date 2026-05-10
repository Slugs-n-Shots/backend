<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Delete(path: '/staff/drinks/{drink}', operationId: 'staffDeleteDrink', summary: 'Delete a drink', tags: ['Staff drinks'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'drink', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 204, description: 'Drink deleted.'),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffDeleteDrinkSwagger
{
}
