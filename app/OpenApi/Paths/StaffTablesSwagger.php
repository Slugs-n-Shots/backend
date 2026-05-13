<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/tables',
    operationId: 'staffListTables',
    summary: 'List tables',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Tables.',
            content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Table'))
        ),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
#[OA\Post(
    path: '/staff/tables',
    operationId: 'staffCreateTable',
    summary: 'Create a table',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Asztal 4'),
            new OA\Property(property: 'active', type: 'boolean', example: true),
        ],
        type: 'object'
    )),
    responses: [
        new OA\Response(response: 201, description: 'Created table.', content: new OA\JsonContent(ref: '#/components/schemas/Table')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
#[OA\Get(
    path: '/staff/tables/{table}',
    operationId: 'staffGetTable',
    summary: 'Get a table',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Table.', content: new OA\JsonContent(ref: '#/components/schemas/Table')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Put(
    path: '/staff/tables/{table}',
    operationId: 'staffUpdateTable',
    summary: 'Update a table',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Asztal 4A'),
            new OA\Property(property: 'active', type: 'boolean', example: true),
        ],
        type: 'object'
    )),
    responses: [
        new OA\Response(response: 200, description: 'Updated table.', content: new OA\JsonContent(ref: '#/components/schemas/Table')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
#[OA\Delete(
    path: '/staff/tables/{table}',
    operationId: 'staffDeleteTable',
    summary: 'Delete a table',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Deleted.'),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Post(
    path: '/staff/tables/{table}/regenerate-guid',
    operationId: 'staffRegenerateTableGuid',
    summary: 'Regenerate a table GUID',
    tags: ['Staff tables'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'table', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Table with regenerated GUID.', content: new OA\JsonContent(ref: '#/components/schemas/Table')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
    ]
)]
final class StaffTablesSwagger
{
}
