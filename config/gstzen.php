<?php

return [
    // GSTZen API base URL
    'api_url' => env('GSTZEN_API_URL', 'https://my.gstzen.in/api/gstin-validator/'),

    // API key (Token header) - set in your .env as GSTZEN_API_KEY
    'api_key' => env('GSTZEN_API_KEY', '5479841c-b3ff-42ba-90bf-cb9866f52321'),

    // Request timeout seconds
    'timeout' => env('GSTZEN_TIMEOUT', 15),
];

