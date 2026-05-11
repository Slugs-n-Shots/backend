<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/menu',
    operationId: 'getStaffMenu',
    summary: 'Get the active drink menu for staff',
    tags: ['Staff menu'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description, category_name and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'en')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Active drinks.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkMenuItem'))),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GetStaffMenuSwagger
{
}
