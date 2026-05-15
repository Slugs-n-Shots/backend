<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Guest',
    title: 'Guest',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: null),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
        new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
        new OA\Property(property: 'reservee', type: ['boolean', 'null'], example: false),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'is_over_18', type: 'boolean', example: true),
        new OA\Property(property: 'age_verified_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
        new OA\Property(property: 'birth_date', type: ['string', 'null'], format: 'date', example: null),
        new OA\Property(property: 'phone', type: ['string', 'null'], example: '+36 30 123 4567'),
        new OA\Property(property: 'address', type: ['string', 'null'], example: '1117 Budapest, Teszt utca 1.'),
        new OA\Property(property: 'anonymized_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
    ]
)]
#[OA\Schema(
    schema: 'GuestAnonymizeBlockingReason',
    title: 'Guest anonymize blocking reason',
    type: 'object',
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'pending_payment'),
        new OA\Property(property: 'message', type: 'string', example: 'You have order items waiting for payment.'),
    ]
)]
#[OA\Schema(
    schema: 'GuestAnonymizeCheckResponse',
    title: 'Guest anonymize check response',
    type: 'object',
    properties: [
        new OA\Property(property: 'can_anonymize', type: 'boolean', example: false),
        new OA\Property(
            property: 'blocking_reasons',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/GuestAnonymizeBlockingReason')
        ),
    ]
)]
#[OA\Schema(
    schema: 'GuestAnonymizeRequest',
    title: 'Guest anonymize request',
    required: ['confirm'],
    type: 'object',
    properties: [
        new OA\Property(property: 'confirm', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'GuestAnonymizeResponse',
    title: 'Guest anonymize response',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The account has been anonymized.'),
    ]
)]
#[OA\Schema(
    schema: 'GuestDataExportResponse',
    title: 'Guest data export response',
    type: 'object',
    properties: [
        new OA\Property(property: 'exported_at', type: 'string', format: 'date-time', example: '2026-05-15T18:30:00Z'),
        new OA\Property(
            property: 'guest',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
                new OA\Property(property: 'middle_name', type: ['string', 'null'], example: null),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane.doe@example.com'),
                new OA\Property(property: 'email_verified_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
                new OA\Property(property: 'reservee', type: ['boolean', 'null'], example: false),
                new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
                new OA\Property(property: 'active', type: 'boolean', example: true),
                new OA\Property(property: 'is_over_18', type: 'boolean', example: true),
                new OA\Property(property: 'age_verified_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                new OA\Property(property: 'birth_date', type: ['string', 'null'], format: 'date', example: '1990-01-02'),
                new OA\Property(property: 'phone', type: ['string', 'null'], example: '+36 30 123 4567'),
                new OA\Property(property: 'address', type: ['string', 'null'], example: '1117 Budapest, Teszt utca 1.'),
                new OA\Property(property: 'anonymized_at', type: ['string', 'null'], format: 'date-time', example: null),
                new OA\Property(property: 'created_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                new OA\Property(property: 'updated_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
            ]
        ),
        new OA\Property(
            property: 'orders',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'guest_id', type: 'integer', example: 1),
                    new OA\Property(property: 'recorded_by', type: ['integer', 'null'], example: null),
                    new OA\Property(property: 'recorded_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                    new OA\Property(property: 'made_by', type: ['integer', 'null'], example: null),
                    new OA\Property(property: 'made_at', type: ['string', 'null'], format: 'date-time', example: null),
                    new OA\Property(property: 'served_by', type: ['integer', 'null'], example: null),
                    new OA\Property(property: 'served_at', type: ['string', 'null'], format: 'date-time', example: null),
                    new OA\Property(property: 'table', type: ['string', 'null'], example: 'A12'),
                    new OA\Property(property: 'status', type: 'string', example: 'served'),
                    new OA\Property(property: 'table_session_id', type: ['integer', 'null'], example: null),
                    new OA\Property(property: 'created_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                    new OA\Property(property: 'updated_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
                    new OA\Property(property: 'details', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuestDataExportOrderDetail')),
                ]
            )
        ),
        new OA\Property(property: 'receipts', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'payment_attempts', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'recent_drinks', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'gdpr_audit_events', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
#[OA\Schema(
    schema: 'GuestDataExportOrderDetail',
    title: 'Guest data export order detail',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 100),
        new OA\Property(property: 'order_id', type: 'integer', example: 10),
        new OA\Property(property: 'drink_unit_id', type: 'integer', example: 5),
        new OA\Property(property: 'ordered_quantity', type: 'integer', example: 2),
        new OA\Property(property: 'promo_id', type: ['integer', 'null'], example: null),
        new OA\Property(property: 'unit_price', type: 'integer', example: 1200),
        new OA\Property(property: 'discount', type: 'integer', example: 0),
        new OA\Property(property: 'receipt_id', type: ['integer', 'null'], example: 50),
        new OA\Property(property: 'payment_status', type: 'string', example: 'paid'),
        new OA\Property(property: 'created_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
        new OA\Property(property: 'updated_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-15T18:30:00Z'),
    ]
)]
final class GuestSchema
{
}
