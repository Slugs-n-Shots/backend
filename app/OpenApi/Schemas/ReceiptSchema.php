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
        new OA\Property(property: 'paid_for', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-05-10T18:05:00+00:00'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'admin_marked_paid'], example: 'card'),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
        new OA\Property(property: 'table_session_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'payment_attempt_id', type: ['integer', 'null'], format: 'int64', example: 1),
        new OA\Property(property: 'access_guid', type: ['string', 'null'], format: 'uuid', example: '9f2f4d8c-0000-0000-0000-000000000000'),
        new OA\Property(property: 'payment_method_name', type: 'string', example: 'card'),
        new OA\Property(
            property: 'details',
            type: 'array',
            nullable: true,
            items: new OA\Items(ref: '#/components/schemas/OrderDetail')
        ),
    ]
)]
final class ReceiptSchema
{
}
