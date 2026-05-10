<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Delete(path: '/staff/categories/{category}', operationId: 'staffDeleteCategory', summary: 'Delete a drink category', tags: ['Staff categories'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 204, description: 'Category deleted.'),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffDeleteCategorySwagger
{
}
