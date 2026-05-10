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
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
    ]
)]
final class GuestSchema
{
}
