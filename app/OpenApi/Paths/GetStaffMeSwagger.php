<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/me', operationId: 'getStaffMe', summary: 'Get authenticated staff user', tags: ['Staff employees'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Authenticated employee wrapped in an array.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Employee'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class GetStaffMeSwagger
{
}
