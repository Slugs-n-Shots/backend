<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/reset-password',
    operationId: 'guestResetPassword',
    summary: 'Reset guest password',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestResetPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Password reset response.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestResetPasswordSwagger
{
}
