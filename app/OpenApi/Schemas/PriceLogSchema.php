<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PriceLog',
    title: 'Price log',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'drink_unit_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'end', type: 'string', format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 1200),
    ]
)]
final class PriceLogSchema
{
}
