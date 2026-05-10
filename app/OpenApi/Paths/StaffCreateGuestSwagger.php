<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/guests', operationId: 'staffCreateGuest', summary: 'Create a guest', tags: ['Staff guests'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestRequest')), responses: [
    new OA\Response(response: 200, description: 'Created guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffCreateGuestSwagger
{
}
