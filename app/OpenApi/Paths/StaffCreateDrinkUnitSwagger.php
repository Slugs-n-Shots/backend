<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/drink-units', operationId: 'staffCreateDrinkUnit', summary: 'Create a drink unit', tags: ['Staff drink units'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkUnitRequest')), responses: [
    new OA\Response(response: 200, description: 'Created drink unit.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkUnit')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffCreateDrinkUnitSwagger
{
}
