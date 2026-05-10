<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/forgot-password',
    operationId: 'guestForgotPassword',
    summary: 'Request guest password reset email',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ForgotPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Password reset email response.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestForgotPasswordSwagger
{
}
