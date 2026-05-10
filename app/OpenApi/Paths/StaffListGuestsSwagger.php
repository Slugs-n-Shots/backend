<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/guests', operationId: 'staffListGuests', summary: 'List guests', tags: ['Staff guests'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Guests.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Guest'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffListGuestsSwagger
{
}
