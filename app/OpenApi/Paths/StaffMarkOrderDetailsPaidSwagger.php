<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/order-details/mark-paid',
    operationId: 'staffMarkOrderDetailsPaid',
    summary: 'Mark pending order details as paid by staff',
    description: 'Staff can mark pending order details paid. If the order detail belongs to a closed table session, only admins can perform this exceptional settlement.',
    tags: ['Staff payments'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StaffMarkPaidRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Payment attempt result.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class StaffMarkOrderDetailsPaidSwagger
{
}
