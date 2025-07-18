<?php

return [
    'paths' => ['api/*', 'oauth/token', 'login', 'logout', 'register', 'admin/*'], // <<< PERBAIKAN: Tambahkan 'oauth/token' di paths
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'], // <<< PASTIKAN INI ADA
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
