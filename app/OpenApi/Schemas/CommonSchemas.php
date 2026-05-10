<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MessageResponse',
    title: 'Message response',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
    ]
)]
#[OA\Schema(
    schema: 'StatusResponse',
    title: 'Status response',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'passwords.sent'),
    ]
)]
#[OA\Schema(
    schema: 'TokenResponse',
    title: 'JWT token response',
    type: 'object',
    required: ['user', 'access_token', 'token_type', 'expires_in'],
    properties: [
        new OA\Property(property: 'user', oneOf: [
            new OA\Schema(ref: '#/components/schemas/Guest'),
            new OA\Schema(ref: '#/components/schemas/Employee'),
        ]),
        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
    ]
)]
#[OA\Schema(
    schema: 'IdPathParameter',
    title: 'ID path parameter',
    type: 'integer',
    format: 'int64',
    example: 1
)]
final class CommonSchemas
{
}
