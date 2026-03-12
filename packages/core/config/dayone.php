<?php

declare(strict_types=1);

return [
    'resolver' => [
        'strategies' => ['route', 'header', 'domain', 'path'],
    ],
    'products' => [
        'cache_ttl' => 3600,
    ],
    'billing' => [
        'provider' => 'stripe',
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_path' => '/webhooks/stripe',
    ],
    'admin' => [
        'path' => 'dayone-admin',
        'auth_guard' => 'web',
    ],
    'openapi' => [
        'enabled' => true,
        'path' => 'api/docs',
    ],
    'plugins' => [
        'auto_discover' => false,
        'directory' => 'plugins',
    ],
    'events' => [
        'auto_discover' => false,
    ],
    'ejection' => [
        'concerns' => ['billing', 'auth', 'admin'],
    ],
];
