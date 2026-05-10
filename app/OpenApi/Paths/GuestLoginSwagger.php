<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/login',
    operationId: 'guestLogin',
    summary: 'Log in as a guest',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Guest token.', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestLoginSwagger
{
}
