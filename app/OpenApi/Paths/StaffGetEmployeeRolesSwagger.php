<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/staff/employees/roles',
    operationId: 'staffGetEmployeeRoles',
    summary: 'List staff roles',
    tags: ['Staff employees'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Localized employee roles.',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 7),
                        new OA\Property(property: 'name_en', type: 'string', example: 'admin'),
                        new OA\Property(property: 'name_hu', type: 'string', example: 'admin'),
                    ]
                )
            )
        ),
    ]
)]
final class StaffGetEmployeeRolesSwagger
{
}
