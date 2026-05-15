<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/current/payments',
    operationId: 'guestCreateTablePayment',
    summary: 'Pay pending order details from the current table session',
    tags: ['Guest payments'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreatePaymentRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Payment attempt result.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestTableCreatePaymentSwagger
{
}
