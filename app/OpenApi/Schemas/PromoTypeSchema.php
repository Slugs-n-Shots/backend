<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PromoType',
    title: 'Promo type',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'description_en', type: 'string', example: 'Happy hour'),
        new OA\Property(property: 'description_hu', type: 'string', example: 'Happy hour'),
        new OA\Property(property: 'description', type: 'string', example: 'Happy hour'),
    ]
)]
final class PromoTypeSchema
{
}
