<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'guest@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'slUGz-R0CKS!'),
    ]
)]
#[OA\Schema(
    schema: 'GuestRegisterRequest',
    type: 'object',
    required: ['first_name', 'last_name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: null),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'slUGz-R0CKS!'),
    ]
)]
#[OA\Schema(
    schema: 'GuestConfirmRegistrationRequest',
    type: 'object',
    required: ['id', 'token'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'token', type: 'string', minLength: 20, maxLength: 20, example: 'abcdefghijklmnopqrst'),
    ]
)]
#[OA\Schema(
    schema: 'ForgotPasswordRequest',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'guest@example.com'),
    ]
)]
#[OA\Schema(
    schema: 'GuestResetPasswordRequest',
    type: 'object',
    required: ['id', 'token', 'password'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'token', type: 'string', minLength: 20, maxLength: 20, example: 'abcdefghijklmnopqrst'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'new-slUGz-R0CKS!'),
    ]
)]
#[OA\Schema(
    schema: 'EmployeeConfirmPasswordRequest',
    type: 'object',
    required: ['token', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'reset-token'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'staff@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'new-slUGz-R0CKS!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'new-slUGz-R0CKS!'),
    ]
)]
#[OA\Schema(
    schema: 'UpdatePasswordRequest',
    type: 'object',
    required: ['current_password', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'slUGz-R0CKS!'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'new-slUGz-R0CKS!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'new-slUGz-R0CKS!'),
    ]
)]
#[OA\Schema(
    schema: 'GuestUpdateSelfRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: null),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
    ]
)]
#[OA\Schema(
    schema: 'MakeOrderCartItem',
    type: 'object',
    required: ['drink_id', 'quantity', 'unit', 'ordered_quantity'],
    properties: [
        new OA\Property(property: 'drink_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 1),
        new OA\Property(property: 'unit', type: 'string', example: 'cup'),
        new OA\Property(property: 'ordered_quantity', type: 'integer', minimum: 1, example: 2),
    ]
)]
#[OA\Schema(
    schema: 'MakeOrderRequest',
    type: 'object',
    required: ['cart'],
    properties: [
        new OA\Property(
            property: 'cart',
            type: 'array',
            minItems: 1,
            items: new OA\Items(ref: '#/components/schemas/MakeOrderCartItem')
        ),
    ]
)]
#[OA\Schema(
    schema: 'MakeOrderResponse',
    type: 'object',
    example: [
        'message' => 'Your selections are being prepared and will be served shortly. Stay tuned!',
        'cart' => [
            [
                'drink_id' => 1,
                'quantity' => 1,
                'unit' => 'cup',
                'ordered_quantity' => 2,
            ],
            [
                'drink_id' => 2,
                'quantity' => 2,
                'unit' => 'glass',
                'ordered_quantity' => 1,
            ],
            [
                'drink_id' => 3,
                'quantity' => 1.5,
                'unit' => 'bottle',
                'ordered_quantity' => 3,
            ],
        ],
        'order' => [
            'id' => 1,
            'guest_id' => 1,
            'recorded_by' => null,
            'recorded_at' => '2026-05-11T14:30:00+00:00',
            'made_by' => null,
            'made_at' => null,
            'served_by' => null,
            'served_at' => null,
            'table' => null,
            'status' => 'in progress',
            'details' => [
                [
                    'id' => 1,
                    'order_id' => 1,
                    'drink_unit_id' => 1,
                    'ordered_quantity' => 2,
                    'promo_id' => null,
                    'unit_price' => 650,
                    'discount' => 0,
                    'receipt_id' => null,
                    'drink_unit' => [
                        'id' => 1,
                        'drink_id' => 1,
                        'quantity' => 1,
                        'unit_en' => 'cup',
                        'unit_hu' => 'cup',
                        'unit_price' => 650,
                        'unit' => 'cup',
                        'drink' => [
                            'id' => 1,
                            'category_id' => 1,
                            'picture' => null,
                            'active' => true,
                            'name' => 'Espresso',
                            'description' => 'Strong coffee.',
                        ],
                    ],
                ],
            ],
        ],
        'discounts' => [],
        'total' => 4300,
    ],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Your selections are being prepared and will be served shortly. Stay tuned!'),
        new OA\Property(property: 'cart', type: 'array', items: new OA\Items(ref: '#/components/schemas/MakeOrderCartItem')),
        new OA\Property(property: 'order', ref: '#/components/schemas/OrderWithDetails'),
        new OA\Property(property: 'discounts', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 4300),
    ]
)]
final class RequestSchemas
{
}
