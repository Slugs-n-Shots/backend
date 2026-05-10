<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(path: '/staff/drinks', operationId: 'staffCreateDrink', summary: 'Create a drink', tags: ['Staff drinks'], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DrinkRequest')), responses: [
    new OA\Response(response: 200, description: 'Created drink.', content: new OA\JsonContent(ref: '#/components/schemas/Drink')),
    new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
])]
final class StaffCreateDrinkSwagger
{
}
