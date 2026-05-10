<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/guests/{guest}', operationId: 'staffGetGuest', summary: 'Get a guest', tags: ['Staff guests'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetGuestSwagger
{
}
