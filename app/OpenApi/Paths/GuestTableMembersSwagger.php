<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/tables/join',
    operationId: 'guestJoinTable',
    summary: 'Request to join the currently reserved table by GUID',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TableJoinRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Created or reopened pending membership request.', content: new OA\JsonContent(ref: '#/components/schemas/TableMemberResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
#[OA\Get(
    path: '/guest/tables/current/members',
    operationId: 'guestCurrentTableMembers',
    summary: 'List current table members and pending requests',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Current table members.', content: new OA\JsonContent(ref: '#/components/schemas/TableMembersResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Post(
    path: '/guest/tables/members/{member}/approve',
    operationId: 'guestApproveTableMember',
    summary: 'Approve a pending table membership request',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Approved membership.', content: new OA\JsonContent(ref: '#/components/schemas/TableMemberResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, description: 'Only the table owner can approve membership requests.'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
    ]
)]
#[OA\Post(
    path: '/guest/tables/members/{member}/reject',
    operationId: 'guestRejectTableMember',
    summary: 'Reject a pending table membership request',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Rejected membership request.'),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, description: 'Only the table owner can reject membership requests.'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
    ]
)]
#[OA\Post(
    path: '/guest/tables/members/{member}/toggle-ordering',
    operationId: 'guestToggleTableMemberOrdering',
    summary: 'Toggle ordering permission for an approved table member',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ToggleTableMemberOrderingRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Updated membership.', content: new OA\JsonContent(ref: '#/components/schemas/TableMemberResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, description: 'Only the table owner can change ordering permission.'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
#[OA\Delete(
    path: '/guest/tables/members/{member}',
    operationId: 'guestRemoveTableMember',
    summary: 'Remove an approved member from the current table',
    tags: ['Guest table members'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'member', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Removed membership.', content: new OA\JsonContent(ref: '#/components/schemas/TableMemberResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 403, description: 'Only the table owner can remove members.'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
    ]
)]
final class GuestTableMembersSwagger
{
}
