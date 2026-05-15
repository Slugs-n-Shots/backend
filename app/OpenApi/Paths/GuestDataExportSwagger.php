<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/guest/me/export',
    operationId: 'guestDataExport',
    summary: 'Export the authenticated guest personal data',
    tags: ['Guest'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Structured guest data export.', content: new OA\JsonContent(ref: '#/components/schemas/GuestDataExportResponse')),
        new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
    ]
)]
final class GuestDataExportSwagger
{
}
