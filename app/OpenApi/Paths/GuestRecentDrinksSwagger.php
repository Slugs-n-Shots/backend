<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/recent-drinks',
    operationId: 'guestRecentDrinks',
    summary: 'Get recently ordered drinks for the authenticated guest',
    tags: ['Guest orders'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'limit',
            in: 'query',
            required: false,
            description: 'Maximum number of unique active drinks to return.',
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10, example: 10)
        ),
        new OA\Parameter(name: 'lang', in: 'query', required: false, description: 'Response locale used for computed name, description, category_name and unit fields.', schema: new OA\Schema(type: 'string', enum: ['en', 'hu'], example: 'hu')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Recently ordered active drinks.', content: new OA\JsonContent(ref: '#/components/schemas/RecentDrinksResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestRecentDrinksSwagger
{
}
