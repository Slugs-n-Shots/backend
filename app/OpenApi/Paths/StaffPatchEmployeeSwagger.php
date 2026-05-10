<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Patch(path: '/staff/employees/{employee}', operationId: 'staffPatchEmployee', summary: 'Partially update an employee', tags: ['Staff employees'], security: [['bearerAuth' => []]], parameters: [
    new OA\Parameter(name: 'employee', in: 'path', required: true, schema: new OA\Schema(type: 'integer', format: 'int64')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EmployeeRequest')), responses: [
    new OA\Response(response: 200, description: 'Updated employee.', content: new OA\JsonContent(ref: '#/components/schemas/Employee')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffPatchEmployeeSwagger
{
}
