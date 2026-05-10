<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/employees', operationId: 'staffCreateEmployee', summary: 'Create an employee', tags: ['Staff employees'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EmployeeRequest')), responses: [
    new OA\Response(response: 200, description: 'Created employee.', content: new OA\JsonContent(ref: '#/components/schemas/Employee')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffCreateEmployeeSwagger
{
}
