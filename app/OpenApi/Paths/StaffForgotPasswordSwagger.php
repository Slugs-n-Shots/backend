<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/forgot-password',
    operationId: 'staffForgotPassword',
    summary: 'Request staff password reset email',
    tags: ['Staff auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ForgotPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Password reset status.', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse')),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class StaffForgotPasswordSwagger
{
}
