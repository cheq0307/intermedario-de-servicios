<?php

return [
    'brand_name' => env('MARKETPLACE_NAME', 'Plaza Local'),

    'service_radius_km' => (int) env('MARKETPLACE_RADIUS_KM', 8),

    'default_commission_basis_points' => (int) env('MARKETPLACE_COMMISSION_BPS', 800),

    'account_types' => [
        'client',
        'provider',
    ],

    'roles' => [
        'client',
        'provider',
        'operator',
        'admin',
    ],

    'payment_methods' => [
        'card',
        'bank_transfer',
        'cash',
    ],
];
