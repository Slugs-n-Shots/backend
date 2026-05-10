<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DrinkCategory',
    title: 'Drink category',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'parent_id', type: ['integer', 'null'], format: 'int64', example: null),
        new OA\Property(property: 'name', type: 'string', example: 'Coffee'),
    ]
)]
#[OA\Schema(
    schema: 'DrinkCategoryLocalized',
    title: 'Drink category with localized fields',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name_en', type: 'string', example: 'Coffee'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Kave'),
        new OA\Property(property: 'parent_id', type: ['integer', 'null'], format: 'int64', example: null),
    ]
)]
final class DrinkCategorySchema
{
}
