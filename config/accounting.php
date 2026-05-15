<?php

return [
    'currency' => env('ACCOUNTING_CURRENCY', 'HUF'),

    'document' => [
        'name' => env('ACCOUNTING_DOCUMENT_NAME', 'Nyugta'),
        'event_description' => env('ACCOUNTING_EVENT_DESCRIPTION', 'Italfogyasztás fizetése'),
    ],

    'issuer' => [
        'name' => env('ACCOUNTING_ISSUER_NAME', "Slug'n'Shots"),
        'address' => env('ACCOUNTING_ISSUER_ADDRESS'),
        'tax_number' => env('ACCOUNTING_ISSUER_TAX_NUMBER'),
        'organizational_unit' => env('ACCOUNTING_ISSUER_ORGANIZATIONAL_UNIT'),
    ],
];
