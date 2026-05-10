<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/confirm-password',
    operationId: 'staffConfirmPassword',
    summary: 'Confirm staff password reset',
    tags: ['Staff auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EmployeeConfirmPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Password reset status.', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse')),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class StaffConfirmPasswordSwagger
{
}
