<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Delete(path: '/staff/guests/{guest}', operationId: 'staffDeleteGuest', summary: 'GDPR anonymize a guest by staff request', tags: ['Staff guests'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'guest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 204, description: 'Guest anonymized.'),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 409, description: 'Guest anonymization is blocked by active business state.', content: new OA\JsonContent(ref: '#/components/schemas/GuestAnonymizeCheckResponse')),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffDeleteGuestSwagger
{
}
