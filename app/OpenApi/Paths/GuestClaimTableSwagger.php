<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/claim',
    operationId: 'guestClaimTable',
    summary: 'Claim an available table by GUID',
    tags: ['Guest tables'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TableClaimRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Claimed table.', content: new OA\JsonContent(ref: '#/components/schemas/TableClaimResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestClaimTableSwagger
{
}
