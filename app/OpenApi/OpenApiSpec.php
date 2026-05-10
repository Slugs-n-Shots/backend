<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.1.0',
    info: new OA\Info(
        title: 'Slugs n Shots API',
        version: '1.0.0',
        description: 'Backend API documentation.'
    ),
    servers: [
        new OA\Server(
            url: '/api',
            description: 'API base path'
        ),
    ]
)]
final class OpenApiSpec
{
}
