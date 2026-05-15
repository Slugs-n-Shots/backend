<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/receipts/{receipt}',
    operationId: 'guestGetReceipt',
    summary: 'Get an accessible receipt for the authenticated guest',
    tags: ['Guest payments'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'receipt', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Receipt.', content: new OA\JsonContent(ref: '#/components/schemas/Receipt')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
final class GuestGetReceiptSwagger
{
}
