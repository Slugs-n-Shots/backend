<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DrinkUnit',
    title: 'Drink unit',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'drink_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 0.5),
        new OA\Property(property: 'unit_en', type: 'string', example: 'l'),
        new OA\Property(property: 'unit_hu', type: 'string', example: 'l'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 1200),
        new OA\Property(property: 'unit', type: 'string', example: 'l'),
    ]
)]
final class DrinkUnitSchema
{
}
