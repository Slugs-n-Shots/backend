<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Drink',
    title: 'Drink',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'name', type: 'string', example: 'Espresso'),
        new OA\Property(property: 'description', type: ['string', 'null'], example: 'Strong coffee.'),
    ]
)]
#[OA\Schema(
    schema: 'DrinkLocalized',
    title: 'Drink with localized fields',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name_en', type: 'string', example: 'Espresso'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Eszpresszo'),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'description_en', type: ['string', 'null'], example: 'Strong coffee.'),
        new OA\Property(property: 'description_hu', type: ['string', 'null'], example: 'Eros kave.'),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'DrinkMenuItem',
    title: 'Drink menu item',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Drink'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'category_name', type: 'string', example: 'Coffee'),
                new OA\Property(
                    property: 'units',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/DrinkUnit')
                ),
            ]
        ),
    ]
)]
final class DrinkSchema
{
}
