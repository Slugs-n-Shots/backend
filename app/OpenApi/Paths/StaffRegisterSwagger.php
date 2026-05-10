<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/register',
    operationId: 'staffRegister',
    summary: 'Staff registration placeholder',
    tags: ['Staff auth'],
    responses: [
        new OA\Response(response: 404, description: 'Page not found.', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string'))),
    ]
)]
final class StaffRegisterSwagger
{
}
