<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/confirm-registration',
    operationId: 'guestConfirmRegistration',
    summary: 'Confirm guest registration',
    tags: ['Guest auth'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestConfirmRegistrationRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Registration confirmed.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestConfirmRegistrationSwagger
{
}
