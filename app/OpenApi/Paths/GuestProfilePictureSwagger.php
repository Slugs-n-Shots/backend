<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/guest/me/picture',
    operationId: 'uploadGuestProfilePicture',
    summary: 'Upload authenticated guest profile picture',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: '#/components/schemas/GuestProfilePictureUploadRequest')
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
    ]
)]
#[OA\Delete(
    path: '/guest/me/picture',
    operationId: 'deleteGuestProfilePicture',
    summary: 'Delete authenticated guest profile picture',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Updated guest.', content: new OA\JsonContent(ref: '#/components/schemas/Guest')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestProfilePictureSwagger
{
}
