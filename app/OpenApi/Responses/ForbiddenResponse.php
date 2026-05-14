<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'Forbidden',
    description: 'The authenticated user is not allowed to perform this action.',
)]
final class ForbiddenResponse
{
}
