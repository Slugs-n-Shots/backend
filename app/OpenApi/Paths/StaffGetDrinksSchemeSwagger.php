<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/drinks/scheme', operationId: 'staffGetDrinksScheme', summary: 'Get drink table scheme', tags: ['Staff drinks'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Drink scheme.', content: new OA\JsonContent(type: 'object')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffGetDrinksSchemeSwagger
{
}
