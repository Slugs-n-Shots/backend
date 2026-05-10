<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/logout',
    operationId: 'staffLogout',
    summary: 'Log out authenticated staff',
    tags: ['Staff auth'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Logout response.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class StaffLogoutSwagger
{
}
