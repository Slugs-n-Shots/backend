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
        new OA\Property(property: 'accounting_document_name', type: ['string', 'null'], example: 'Nyugta'),
        new OA\Property(property: 'accounting_document_number', type: ['string', 'null'], example: 'R00000001'),
        new OA\Property(property: 'issuer_name', type: ['string', 'null'], example: 'Slugs-n-Shots Kft.'),
        new OA\Property(property: 'issuer_address', type: ['string', 'null'], example: '1099 Budapest, Teszt utca 1.'),
        new OA\Property(property: 'issuer_tax_number', type: ['string', 'null'], example: '12345678-2-42'),
        new OA\Property(property: 'issuer_organizational_unit', type: ['string', 'null'], example: 'Pult'),
        new OA\Property(property: 'customer_type', type: ['string', 'null'], enum: ['individual', 'company'], example: 'company'),
        new OA\Property(property: 'customer_name', type: ['string', 'null'], example: 'Teszt Partner Kft.'),
        new OA\Property(property: 'customer_address', type: ['string', 'null'], example: '1117 Budapest, Céges út 2.'),
        new OA\Property(property: 'customer_tax_number', type: ['string', 'null'], example: '87654321-2-43'),
        new OA\Property(property: 'customer_email', type: ['string', 'null'], format: 'email', example: 'szamla@example.com'),
        new OA\Property(property: 'performance_at', type: ['string', 'null'], format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'economic_event_description', type: ['string', 'null'], example: 'Italfogyasztás fizetése'),
        new OA\Property(property: 'accounting_currency', type: ['string', 'null'], example: 'HUF'),
        new OA\Property(property: 'accounting_gross_total', type: ['integer', 'null'], example: 2000),
        new OA\Property(property: 'accounting_items', type: 'array', nullable: true, items: new OA\Items(ref: '#/components/schemas/ReceiptAccountingItem')),
        new OA\Property(property: 'bookkeeping_reference', type: ['string', 'null'], example: null),
        new OA\Property(property: 'bookkeeping_posted_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'bookkeeping_verified_by', type: ['string', 'null'], example: null),
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
