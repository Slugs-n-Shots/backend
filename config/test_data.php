<?php

return [
    'presets' => [
        'small' => [
            'guests' => 20,
            'employees' => 5,
            'tables' => 8,
            'orders' => 100,
            'days' => 14,
            'with_gdpr_cases' => true,
        ],
        'demo' => [
            'guests' => 120,
            'employees' => 10,
            'tables' => 20,
            'orders' => 1500,
            'days' => 60,
            'with_gdpr_cases' => true,
        ],
        'load' => [
            'guests' => 1000,
            'employees' => 30,
            'tables' => 60,
            'orders' => 50000,
            'days' => 365,
            'with_gdpr_cases' => true,
        ],
    ],
];
