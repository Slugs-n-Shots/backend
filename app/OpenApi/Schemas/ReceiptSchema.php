<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Receipt',
    title: 'Receipt',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'serno', type: 'string', example: 'R0000001'),
        new OA\Property(property: 'guest_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'issued_at', type: 'string', format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'paid_for', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-05-10T18:05:00+00:00'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card'], example: 'card'),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
        new OA\Property(property: 'payment_method_name', type: 'string', example: 'card'),
    ]
)]
final class ReceiptSchema
{
}
