<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/current/close',
    operationId: 'guestCloseCurrentTable',
    summary: 'Close the current table session owned by the guest',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Closed table session.', content: new OA\JsonContent(ref: '#/components/schemas/TableClaimResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 409, description: 'The current table still has pending order details.'),
    ]
)]
final class GuestCloseCurrentTableSwagger
{
}
