<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/me/anonymize/check',
    operationId: 'guestAnonymizeCheck',
    summary: 'Check whether the authenticated guest can anonymize their account',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Anonymization availability.', content: new OA\JsonContent(ref: '#/components/schemas/GuestAnonymizeCheckResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
#[OA\Post(
    path: '/guest/me/anonymize',
    operationId: 'guestAnonymize',
    summary: 'Anonymize the authenticated guest account',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GuestAnonymizeRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Guest account anonymized.', content: new OA\JsonContent(ref: '#/components/schemas/GuestAnonymizeResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 409, description: 'Anonymization is blocked by active business state.', content: new OA\JsonContent(ref: '#/components/schemas/GuestAnonymizeCheckResponse')),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
final class GuestAnonymizeSwagger
{
}
