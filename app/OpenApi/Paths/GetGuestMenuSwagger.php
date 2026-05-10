<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/menu',
    operationId: 'getGuestMenu',
    summary: 'Get the active drink menu',
    tags: ['Guest menu'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Active drinks visible for guests.',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/DrinkMenuItem')
            )
        ),
    ]
)]
final class GetGuestMenuSwagger
{
}
