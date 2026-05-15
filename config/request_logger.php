<?php

return [
    'enabled' => env('REQUEST_LOGGER_ENABLED', true),
    'mask_sensitive' => env('REQUEST_LOGGER_MASK_SENSITIVE', true),
    'sensitive_keys' => [
        'access_token',
        'address',
        'authorization',
        'birth_date',
        'confirm_token',
        'cookie',
        'current_password',
        'email',
        'password',
        'password_confirmation',
        'phone',
        'pw_reset_token',
        'remember_token',
        'set-cookie',
        'tax_number',
        'token',
        'x-xsrf-token',
    ],
];
