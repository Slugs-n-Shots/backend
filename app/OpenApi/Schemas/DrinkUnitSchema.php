<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DrinkUnit',
    title: 'Drink unit',
    type: 'object',
    example: [
        'id' => 1,
        'drink_id' => 1,
        'quantity' => 1,
        'unit_en' => 'cup',
        'unit_hu' => 'csésze',
        'unit_price' => 650,
        'unit' => 'cup',
    ],
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
#[OA\Schema(
    schema: 'DrinkMenuUnit',
    title: 'Drink menu unit',
    type: 'object',
    example: [
        'quantity' => 1,
        'unit_en' => 'glass',
        'unit_hu' => 'pohár',
        'unit_price' => 900,
        'unit' => 'glass',
    ],
    properties: [
        new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 1),
        new OA\Property(property: 'unit_en', type: 'string', example: 'glass'),
        new OA\Property(property: 'unit_hu', type: 'string', example: 'pohár'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 900),
        new OA\Property(property: 'unit', type: 'string', example: 'glass'),
    ]
)]
final class DrinkUnitSchema
{
}
