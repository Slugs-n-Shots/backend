<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/staff/orders',
    operationId: 'staffCreateOrder',
    summary: 'Create an order for a guest as staff',
    tags: ['Staff orders'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StaffCreateOrderRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Created order summary.', content: new OA\JsonContent(ref: '#/components/schemas/MakeOrderResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class StaffCreateOrderSwagger
{
}
