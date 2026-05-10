<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drink-units', operationId: 'staffListDrinkUnits', summary: 'List drink units', tags: ['Staff drink units'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Drink units.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkUnit'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffListDrinkUnitsSwagger
{
}
