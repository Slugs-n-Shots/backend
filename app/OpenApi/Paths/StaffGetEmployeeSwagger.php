<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/staff/employees/{employee}', operationId: 'staffGetEmployee', summary: 'Get an employee', tags: ['Staff employees'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'employee', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], responses: [
    new OA\Response(response: 200, description: 'Employee.', content: new OA\JsonContent(ref: '#/components/schemas/Employee')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
])]
final class StaffGetEmployeeSwagger
{
}
