<?php

return [
    'default_staff_spending_limit' => env('TABLES_DEFAULT_STAFF_SPENDING_LIMIT') !== null
        ? (int) env('TABLES_DEFAULT_STAFF_SPENDING_LIMIT')
        : null,
];
