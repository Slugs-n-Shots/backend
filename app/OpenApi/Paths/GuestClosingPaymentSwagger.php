<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/current/closing-payment',
    operationId: 'guestCreateClosingPayment',
    summary: 'Pay all pending order details for the current table session as owner',
    tags: ['Guest payments'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ClosingPaymentRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Payment attempt result.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestClosingPaymentSwagger
{
}
