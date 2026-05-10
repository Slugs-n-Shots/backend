<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/orders',
    operationId: 'guestMakeOrder',
    summary: 'Create an order as a guest',
    tags: ['Guest orders'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MakeOrderRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Created order summary.', content: new OA\JsonContent(ref: '#/components/schemas/MakeOrderResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestMakeOrderSwagger
{
}
