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

    'payment_driver' => env('MARKETPLACE_PAYMENT_DRIVER', 'fake'),
    'allow_fake_payments' => (bool) env('MARKETPLACE_ALLOW_FAKE_PAYMENTS', false),
    'reservation_minutes' => (int) env('MARKETPLACE_RESERVATION_MINUTES', 20),
    'operations_alert_email' => env('OPERATIONS_ALERT_EMAIL'),
];
