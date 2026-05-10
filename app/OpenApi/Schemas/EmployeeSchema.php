<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Employee',
    title: 'Employee',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'first_name', type: 'string', example: 'Slugs'),
        new OA\Property(property: 'middle_name', type: ['string', 'null'], example: 'Admin'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Shots'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'staff@example.com'),
        new OA\Property(property: 'role_code', type: 'integer', example: 7),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'role', type: 'string', example: 'admin'),
        new OA\Property(property: 'name', type: 'string', example: 'Slugs Admin Shots'),
    ]
)]
final class EmployeeSchema
{
}
