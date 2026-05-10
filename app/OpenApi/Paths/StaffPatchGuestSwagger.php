<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Patch(path: '/staff/guests/{guest}', operationId: 'staffPatchGuest', summary: 'Partially update a guest', tags: ['Staff guests'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestRequest')), responses: [
    new OA\Response(response: 200, description: 'Updated guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffPatchGuestSwagger
{
}
