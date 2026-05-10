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
        new OA\Parameter(name: 'with', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'category,units')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Drink card.', content: new OA\JsonContent(ref: '#/components/schemas/DrinkMenuItem')),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
final class GetGuestDrinkCardSwagger
{
}
