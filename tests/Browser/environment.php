<?php

// Isolated browser fixtures only. Never use the application's normal database or credentials.
$root = dirname(__DIR__, 2);
foreach ([
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'true', 'APP_URL' => 'http://127.0.0.1:8098',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $root.'/storage/framework/testing/chat-browser.sqlite',
    'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'plaza_chat_browser_test', 'SESSION_SECURE_COOKIE' => 'false',
    'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
    'BROADCAST_CONNECTION' => 'reverb', 'REVERB_APP_ID' => 'browser-test', 'REVERB_APP_KEY' => 'browser-test-key', 'REVERB_APP_SECRET' => 'browser-test-secret',
    'REVERB_HOST' => '127.0.0.1', 'REVERB_PORT' => '8099', 'REVERB_SCHEME' => 'http',
    'REVERB_SERVER_HOST' => '127.0.0.1', 'REVERB_SERVER_PORT' => '8099', 'REVERB_ALLOWED_ORIGINS' => '127.0.0.1',
    'REVERB_PUBLIC_HOST' => '127.0.0.1', 'REVERB_PUBLIC_PORT' => '8099', 'REVERB_PUBLIC_SCHEME' => 'http',
    'PHONE_VERIFICATION_ENABLED' => 'false', 'MARKETPLACE_PAYMENT_DRIVER' => 'fake',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
