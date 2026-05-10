<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/me',
    operationId: 'updateGuestMe',
    summary: 'Update the authenticated guest',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestUpdateSelfRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Updated guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class UpdateGuestMeSwagger
{
}
