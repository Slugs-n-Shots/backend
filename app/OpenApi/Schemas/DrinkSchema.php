<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Drink',
    title: 'Drink',
    type: 'object',
    example: [
        'id' => 1,
        'category_id' => 1,
        'picture' => null,
        'active' => true,
        'name' => 'Espresso',
        'description' => 'Strong coffee.',
        'units' => [[
            'id' => 1,
            'drink_id' => 1,
            'quantity' => 1,
            'unit_en' => 'cup',
            'unit_hu' => 'csésze',
            'unit_price' => 650,
            'unit' => 'cup',
        ]],
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'name', type: 'string', example: 'Espresso'),
        new OA\Property(property: 'description', type: ['string', 'null'], example: 'Strong coffee.'),
        new OA\Property(property: 'category', ref: '#/components/schemas/DrinkCategory', nullable: true),
        new OA\Property(property: 'units', type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkUnit')),
    ]
)]
#[OA\Schema(
    schema: 'DrinkLocalized',
    title: 'Drink with localized fields',
    type: 'object',
    example: [
        'id' => 1,
        'name_en' => 'Espresso',
        'name_hu' => 'Eszpresszo',
        'category_id' => 1,
        'description_en' => 'Strong coffee.',
        'description_hu' => 'Eros kave.',
        'picture' => null,
        'active' => true,
        'units' => [[
            'id' => 1,
            'drink_id' => 1,
            'quantity' => 1,
            'unit_en' => 'cup',
            'unit_hu' => 'csésze',
            'unit_price' => 650,
            'unit' => 'cup',
        ]],
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name_en', type: 'string', example: 'Espresso'),
        new OA\Property(property: 'name_hu', type: 'string', example: 'Eszpresszo'),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'description_en', type: ['string', 'null'], example: 'Strong coffee.'),
        new OA\Property(property: 'description_hu', type: ['string', 'null'], example: 'Eros kave.'),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'category', ref: '#/components/schemas/DrinkCategory', nullable: true),
        new OA\Property(property: 'units', type: 'array', items: new OA\Items(ref: '#/components/schemas/DrinkUnit')),
    ]
)]
#[OA\Schema(
    schema: 'DrinkCard',
    title: 'Drink card',
    type: 'object',
    example: [
        'id' => 1,
        'picture' => null,
        'name' => 'Lemonade',
        'description' => 'Fresh lemonade.',
        'category_name' => 'Soft Drinks',
        'units' => [[
            'quantity' => 1,
            'unit_en' => 'glass',
            'unit_hu' => 'pohár',
            'unit_price' => 900,
            'unit' => 'glass',
        ]],
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'name', type: 'string', example: 'Lemonade'),
        new OA\Property(property: 'description', type: ['string', 'null'], example: 'Fresh lemonade.'),
        new OA\Property(property: 'category_name', type: 'string', example: 'Soft Drinks'),
        new OA\Property(
            property: 'units',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DrinkMenuUnit')
        ),
    ]
)]
#[OA\Schema(
    schema: 'DrinkMenuItem',
    title: 'Drink menu item',
    type: 'object',
    example: [
        'id' => 1,
        'category_id' => 1,
        'picture' => null,
        'name' => 'Lemonade',
        'description' => 'Fresh lemonade.',
        'category_name' => 'Soft Drinks',
        'units' => [[
            'quantity' => 1,
            'unit_en' => 'glass',
            'unit_hu' => 'pohár',
            'unit_price' => 900,
            'unit' => 'glass',
        ]],
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'picture', type: ['string', 'null'], example: null),
        new OA\Property(property: 'name', type: 'string', example: 'Lemonade'),
        new OA\Property(property: 'description', type: ['string', 'null'], example: 'Fresh lemonade.'),
        new OA\Property(property: 'category_name', type: 'string', example: 'Soft Drinks'),
        new OA\Property(
            property: 'units',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DrinkMenuUnit')
        ),
    ]
)]
final class DrinkSchema
{
}
