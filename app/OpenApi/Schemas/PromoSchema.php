<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Promo',
    title: 'Promo',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'promo_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-05-10T18:00:00+00:00'),
        new OA\Property(property: 'end', type: ['string', 'null'], format: 'date-time', example: null),
        new OA\Property(property: 'category_id', type: ['integer', 'null'], format: 'int64', example: 1),
    ]
)]
final class PromoSchema
{
}
