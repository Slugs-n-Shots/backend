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
        new OA\Property(property: 'message', type: 'string', example: 'Van fizetésre váró rendelési tételed.'),
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
        new OA\Property(property: 'message', type: 'string', example: 'A fiók anonimizálva lett.'),
    ]
)]
final class GuestSchema
{
}
