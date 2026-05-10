<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/login',
    operationId: 'staffLogin',
    summary: 'Log in as staff',
    tags: ['Staff auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Staff token.', content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class StaffLoginSwagger
{
}
