<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/verify/resend',
    operationId: 'guestResendEmailVerificationMail',
    summary: 'Resend guest email verification mail',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ForgotPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Verification resend response.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestResendEmailVerificationMailSwagger
{
}
