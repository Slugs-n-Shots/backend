<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Table',
    title: 'Table',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(property: 'name', type: 'string', example: 'Asztal 4'),
        new OA\Property(property: 'guid', type: 'string', format: 'uuid', example: '9f2f4d8c-0000-0000-0000-000000000000'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'status', type: 'string', enum: ['available', 'reserved', 'inactive'], example: 'available'),
    ]
)]
#[OA\Schema(
    schema: 'GuestTable',
    title: 'Guest table',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(property: 'name', type: 'string', example: 'Asztal 4'),
        new OA\Property(property: 'guid', type: 'string', format: 'uuid', example: '9f2f4d8c-0000-0000-0000-000000000000'),
        new OA\Property(property: 'status', type: 'string', enum: ['available', 'reserved', 'inactive'], example: 'reserved'),
        new OA\Property(property: 'is_owner', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'TableSession',
    title: 'Table session',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 34),
        new OA\Property(property: 'table_id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(property: 'owner_guest_id', type: 'integer', format: 'int64', example: 8),
        new OA\Property(property: 'business_date', type: 'string', format: 'date', example: '2026-05-14'),
        new OA\Property(property: 'opened_at', type: 'string', format: 'date-time', example: '2026-05-14T18:30:00.000000Z'),
        new OA\Property(property: 'closed_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed'], example: 'open'),
    ]
)]
#[OA\Schema(
    schema: 'TableClaimRequest',
    title: 'Table claim request',
    required: ['guid'],
    properties: [
        new OA\Property(property: 'guid', type: 'string', format: 'uuid', example: '9f2f4d8c-0000-0000-0000-000000000000'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'TableClaimResponse',
    title: 'Table claim response',
    type: 'object',
    properties: [
        new OA\Property(property: 'table', ref: '#/components/schemas/GuestTable'),
        new OA\Property(property: 'table_session', ref: '#/components/schemas/TableSession'),
    ]
)]
#[OA\Schema(
    schema: 'AvailableTablesResponse',
    title: 'Available tables response',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'tables',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/GuestTable')
        ),
    ]
)]
#[OA\Schema(
    schema: 'CurrentTableResponse',
    title: 'Current table response',
    type: 'object',
    properties: [
        new OA\Property(property: 'table', oneOf: [
            new OA\Schema(ref: '#/components/schemas/GuestTable'),
            new OA\Schema(type: 'null'),
        ]),
        new OA\Property(property: 'table_session', oneOf: [
            new OA\Schema(ref: '#/components/schemas/TableSession'),
            new OA\Schema(type: 'null'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'TableJoinRequest',
    title: 'Table join request',
    required: ['guid'],
    properties: [
        new OA\Property(property: 'guid', type: 'string', format: 'uuid', example: '9f2f4d8c-0000-0000-0000-000000000000'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'TableMember',
    title: 'Table member',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 55),
        new OA\Property(property: 'table_session_id', type: 'integer', format: 'int64', example: 34),
        new OA\Property(property: 'guest_id', type: 'integer', format: 'int64', example: 9),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'member'], example: 'member'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'denied', 'removed'], example: 'pending'),
        new OA\Property(property: 'can_order', type: 'boolean', example: true),
        new OA\Property(property: 'approved_by_guest_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'approved_at', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'removed_at', type: ['string', 'null'], format: 'date-time', example: null),
    ]
)]
#[OA\Schema(
    schema: 'TableMemberListItem',
    title: 'Table member list item',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: ['integer', 'null'], format: 'int64', example: 55),
        new OA\Property(property: 'guest_id', type: 'integer', format: 'int64', example: 9),
        new OA\Property(property: 'name', type: 'string', example: 'Minta Vendég'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'member'], example: 'member'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'denied', 'removed'], example: 'approved'),
        new OA\Property(property: 'can_order', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'TableMemberResponse',
    title: 'Table member response',
    type: 'object',
    properties: [
        new OA\Property(property: 'membership', ref: '#/components/schemas/TableMember'),
    ]
)]
#[OA\Schema(
    schema: 'TableMembersResponse',
    title: 'Table members response',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'members',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TableMemberListItem')
        ),
        new OA\Property(
            property: 'pending',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TableMemberListItem')
        ),
    ]
)]
#[OA\Schema(
    schema: 'ToggleTableMemberOrderingRequest',
    title: 'Toggle table member ordering request',
    required: ['can_order'],
    properties: [
        new OA\Property(property: 'can_order', type: 'boolean', example: false),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'GuestTableMemberActionResponse',
    title: 'Guest table member action response',
    type: 'object',
    properties: [
        new OA\Property(property: 'membership', ref: '#/components/schemas/TableMember'),
    ]
)]
final class TableSchema
{
}
