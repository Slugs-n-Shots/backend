<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Order',
    title: 'Order',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'guest_id', type: ['integer', 'null'], format: 'int64', example: 1),
        new OA\Property(property: 'recorded_by', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'recorded_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'made_by', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'made_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'served_by', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'served_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
        new OA\Property(property: 'status', type: 'string', enum: ['open', 'preparing', 'ready', 'served', 'cancelled'], example: 'open'),
        new OA\Property(property: 'table_session_id', type: ['integer', 'null'], format: 'int64', example: 3),
    ]
)]
#[OA\Schema(
    schema: 'OrderWithDetails',
    title: 'Order with details',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Order'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'details',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/OrderDetailWithDrinkUnit')
                ),
            ]
        ),
    ]
)]
final class OrderSchema
{
}
