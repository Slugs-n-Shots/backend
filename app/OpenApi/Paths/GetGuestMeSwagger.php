<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/me',
    operationId: 'getGuestMe',
    summary: 'Get the authenticated guest',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Authenticated guest.',
            content: new OA\JsonContent(ref: '#/components/schemas/Guest')
        ),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GetGuestMeSwagger
{
}
