<?php

return [
    'brand_name' => env('MARKETPLACE_NAME', 'Plaza Local'),

    'service_radius_km' => (int) env('MARKETPLACE_RADIUS_KM', 8),

    'business_timezone' => env('MARKETPLACE_BUSINESS_TIMEZONE', 'America/Mexico_City'),

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
    'job_posting_fee_amount' => (int) env('MARKETPLACE_JOB_POSTING_FEE_CENTS', 9900),
    'job_posting_days' => (int) env('MARKETPLACE_JOB_POSTING_DAYS', 30),
    'verification_review_fee_amount' => (int) env('MARKETPLACE_VERIFICATION_REVIEW_FEE_CENTS', 0),
    'promotion_prices' => [
        7 => (int) env('MARKETPLACE_PROMOTION_7_DAYS_CENTS', 4900),
        15 => (int) env('MARKETPLACE_PROMOTION_15_DAYS_CENTS', 8900),
        30 => (int) env('MARKETPLACE_PROMOTION_30_DAYS_CENTS', 14900),
    ],
];
