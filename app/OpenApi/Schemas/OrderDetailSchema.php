<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderDetail',
    title: 'Order detail',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'drink_unit_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'ordered_quantity', type: 'integer', example: 2),
        new OA\Property(property: 'promo_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'unit_price', type: 'integer', example: 1200),
        new OA\Property(property: 'discount', type: ['number', 'null'], format: 'float', example: 0),
        new OA\Property(property: 'receipt_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'payment_status', type: 'string', enum: ['pending', 'paid'], example: 'pending'),
    ]
)]
#[OA\Schema(
    schema: 'OrderDrinkUnit',
    title: 'Order drink unit',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'drink_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 1),
        new OA\Property(property: 'unit_en', type: 'string', example: 'cup'),
        new OA\Property(property: 'unit_hu', type: 'string', example: 'cup'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 650),
        new OA\Property(property: 'unit', type: 'string', example: 'cup'),
        new OA\Property(property: 'drink', ref: '#/components/schemas/Drink'),
    ]
)]
#[OA\Schema(
    schema: 'OrderDetailWithDrinkUnit',
    title: 'Order detail with drink unit',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/OrderDetail'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'drink_unit', ref: '#/components/schemas/OrderDrinkUnit'),
            ]
        ),
    ]
)]
final class OrderDetailSchema
{
}
