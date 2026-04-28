<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
        env('FRONTEND_URL', 'http://localhost:5173'),
        // Capacitor (iOS usa capacitor://, Android usa https://localhost)
        'capacitor://localhost',
        'https://localhost',
        'http://localhost',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // true porque usamos tokens Bearer; necessário para preflight com Authorization header
    'supports_credentials' => true,

];
