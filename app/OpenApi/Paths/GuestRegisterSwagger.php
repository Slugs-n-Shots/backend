<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/register',
    operationId: 'guestRegister',
    summary: 'Register a guest',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/GuestRegisterRequest')
    ),
    responses: [
        new OA\Response(response: 200, description: 'Registration accepted.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestRegisterSwagger
{
}
