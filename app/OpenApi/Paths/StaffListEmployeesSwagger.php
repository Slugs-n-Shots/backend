<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/employees', operationId: 'staffListEmployees', summary: 'List employees', tags: ['Staff employees'], security: [['bearerAuth' => []]], responses: [
    new OA\Response(response: 200, description: 'Employees.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Employee'))),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
])]
final class StaffListEmployeesSwagger
{
}
