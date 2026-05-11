<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/drinks/card/{drink}',
    operationId: 'getGuestDrinkCard',
    summary: 'Get a public drink card',
    tags: ['Guest menu'],
    parameters: [
        new OA\Parameter(name: 'drink', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
        new OA\Parameter(name: 'with', in: 'query', required: false, description: 'Comma-separated relations to include. Allowed values: category, units.', schema: new OA\Schema(type: 'string', example: 'units')),
        new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description, category_name and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'en')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Drink card.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkCard')),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
final class GetGuestDrinkCardSwagger
{
}
