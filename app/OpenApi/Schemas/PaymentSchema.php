<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentAttempt',
    title: 'Payment attempt',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'guest_id', type: ['integer', 'null'], format: 'int64', example: 12),
        new OA\Property(property: 'employee_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'table_session_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'receipt_id', type: ['integer', 'null'], format: 'int64', example: 10),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'succeeded', 'failed', 'abandoned'], example: 'succeeded'),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'admin_marked_paid'], example: 'card'),
        new OA\Property(property: 'amount', type: 'integer', example: 2200),
        new OA\Property(property: 'currency', type: 'string', example: 'HUF'),
        new OA\Property(property: 'started_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:00:00+00:00'),
        new OA\Property(property: 'finished_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:01:00+00:00'),
    ]
)]
#[OA\Schema(
    schema: 'PaymentEvent',
    title: 'Payment event',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'payment_attempt_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'event_type', type: 'string', example: 'payment_succeeded'),
        new OA\Property(property: 'actor_guest_id', type: ['integer', 'null'], format: 'int64', example: 12),
        new OA\Property(property: 'actor_employee_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'order_detail_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'receipt_id', type: ['integer', 'null'], format: 'int64', example: 10),
        new OA\Property(property: 'created_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:01:00+00:00'),
    ]
)]
#[OA\Schema(
    schema: 'CreatePaymentRequest',
    title: 'Create payment request',
    required: ['order_detail_ids', 'payment_method'],
    properties: [
        new OA\Property(property: 'order_detail_ids', type: 'array', minItems: 1, items: new OA\Items(type: 'integer', format: 'int64'), example: [1, 2]),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card'], example: 'card'),
        new OA\Property(
            property: 'simulate_result',
            description: 'Only valid in local/testing environments.',
            type: 'string',
            enum: ['succeeded', 'failed', 'abandoned'],
            example: 'failed',
            nullable: true
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ClosingPaymentRequest',
    title: 'Closing payment request',
    required: ['payment_method'],
    properties: [
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card'], example: 'cash'),
        new OA\Property(
            property: 'simulate_result',
            description: 'Only valid in local/testing environments.',
            type: 'string',
            enum: ['succeeded', 'failed', 'abandoned'],
            example: 'failed',
            nullable: true
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'StaffMarkPaidRequest',
    title: 'Staff mark paid request',
    required: ['order_detail_ids'],
    properties: [
        new OA\Property(property: 'order_detail_ids', type: 'array', minItems: 1, items: new OA\Items(type: 'integer', format: 'int64'), example: [1, 2]),
        new OA\Property(property: 'memo', type: ['string', 'null'], maxLength: 1000, example: 'Pultnál rendezve'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaymentResponse',
    title: 'Payment response',
    type: 'object',
    properties: [
        new OA\Property(property: 'payment', ref: '#/components/schemas/PaymentAttempt'),
        new OA\Property(property: 'receipt', oneOf: [
            new OA\Schema(ref: '#/components/schemas/Receipt'),
            new OA\Schema(type: 'null'),
        ]),
    ]
)]
final class PaymentSchema
{
}
