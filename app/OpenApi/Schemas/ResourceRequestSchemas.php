<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DrinkRequest',
    type: 'object',
    required: ['name_en', 'name_hu', 'category_id', 'units'],
    example: [
        'name_en' => 'Espresso',
        'name_hu' => 'Eszpresszo',
        'category_id' => 1,
        'description_en' => 'Strong coffee.',
        'description_hu' => 'Eros kave.',
        'active' => true,
        'units' => [[
            'quantity' => 1,
            'unit_en' => 'cup',
            'unit_hu' => 'csésze',
            'unit_price' => 650,
        ]],
    ],
    properties: [
        new OA\Property(property: 'name_en', type: 'string', example: 'Espresso'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Eszpresszo'),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'description_en', type: ['string', 'null'], example: 'Strong coffee.'),
        new OA\Property(property: 'description_hu', type: ['string', 'null'], example: 'Eros kave.'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'units', type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkUnitRequest')),
    ]
)]
#[OA\Schema(
    schema: 'DrinkUpdateRequest',
    type: 'object',
    example: [
        'name_en' => 'Double Espresso',
        'active' => true,
        'units' => [[
            'id' => 1,
            'quantity' => 2,
            'unit_en' => 'cup',
            'unit_hu' => 'csésze',
            'unit_price' => 950,
        ]],
    ],
    properties: [
        new OA\Property(property: 'name_en', type: 'string', example: 'Double Espresso'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Dupla eszpresszo'),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'description_en', type: ['string', 'null'], example: 'Strong coffee.'),
        new OA\Property(property: 'description_hu', type: ['string', 'null'], example: 'Eros kave.'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(
            property: 'units',
            description: 'When present, this replaces the drink unit list: omitted existing unit IDs are deleted, existing IDs are updated, and items without an ID are created.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DrinkUnitUpdateRequest')
        ),
    ]
)]
#[OA\Schema(
    schema: 'DrinkCategoryRequest',
    type: 'object',
    required: ['name_en', 'name_hu'],
    properties: [
        new OA\Property(property: 'name_en', type: 'string', example: 'Coffee'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Kave'),
        new OA\Property(property: 'parent_id', type: ['integer', 'null'], format: 'int64', example: null),
    ]
)]
#[OA\Schema(
    schema: 'DrinkUnitRequest',
    type: 'object',
    required: ['quantity', 'unit_en', 'unit_hu', 'unit_price'],
    example: [
        'quantity' => 1,
        'unit_en' => 'cup',
        'unit_hu' => 'csésze',
        'unit_price' => 650,
    ],
    properties: [
        new OA\Property(property: 'drink_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 0.5),
        new OA\Property(property: 'unit_en', type: 'string', example: 'l'),
        new OA\Property(property: 'unit_hu', type: 'string', example: 'l'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 1200),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'DrinkUnitUpdateRequest',
    type: 'object',
    required: ['quantity', 'unit_en', 'unit_hu', 'unit_price'],
    example: [
        'id' => 1,
        'quantity' => 2,
        'unit_en' => 'cup',
        'unit_hu' => 'csésze',
        'unit_price' => 950,
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 2),
        new OA\Property(property: 'unit_en', type: 'string', example: 'cup'),
        new OA\Property(property: 'unit_hu', type: 'string', example: 'csésze'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 950),
    ]
)]
#[OA\Schema(
    schema: 'EmployeeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', example: 'Slugs'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: 'Admin'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Shots'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'staff@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'slUGz-R0CKS!'),
        new OA\Property(property: 'role_code', type: 'integer', example: 7),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'GuestRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: null),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'slUGz-R0CKS!'),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'OrderRequest',
    type: 'object',
    required: ['guest_id'],
    properties: [
        new OA\Property(property: 'guest_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'recorded_by', type: ['integer', 'null'], format: 'int64', example: 1),
        new OA\Property(property: 'recorded_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'made_by', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'made_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'served_by', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'served_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
    ]
)]
#[OA\Schema(
    schema: 'OrderDetailRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'drink_unit_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'ordered_quantity', type: 'integer', example: 2),
        new OA\Property(property: 'promo_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'unit_price', type: 'integer', example: 1200),
        new OA\Property(property: 'discount', type: ['number', 'null'], format: 'float', example: 0),
        new OA\Property(property: 'receipt_id', type: ['integer', 'null'], format: 'int64', example: null),
    ]
)]
#[OA\Schema(
    schema: 'PromoRequest',
    type: 'object',
    required: ['start', 'category_id'],
    properties: [
        new OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'end', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'PromoTypeRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'description_en', type: ['string', 'null'], example: 'Happy hour'),
        new OA\Property(property: 'description_hu', type: ['string', 'null'], example: 'Happy hour'),
    ]
)]
#[OA\Schema(
    schema: 'ReceiptRequest',
    type: 'object',
    required: ['serno', 'guest_id', 'issued_at', 'paid_for', 'paid_at', 'payment_method'],
    properties: [
        new OA\Property(property: 'serno', type: 'string', example: 'R0000001'),
        new OA\Property(property: 'guest_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'issued_at', type: 'string', format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'paid_for', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-05-10T18:05:00+00:00'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card'], example: 'card'),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
    ]
)]
final class ResourceRequestSchemas
{
}
