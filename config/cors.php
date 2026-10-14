<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Famville has no routes/api.php and no cross-origin consumer — every
    // request, including the one JSON endpoint (GET /availability/{type}/
    // {id}), is same-origin, built via Laravel's own route() helper from
    // the app's own pages. Same-origin requests never need CORS headers at
    // all; this config exists so that if a cross-origin consumer is ever
    // added deliberately, it has to be opted into explicitly here rather
    // than inheriting Laravel's wide-open '*' default.
    'paths' => ['availability/*'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    // No endpoint in this app relies on cookies/session being sent
    // cross-origin — leave this false unless a real cross-origin,
    // credentialed consumer is added later.
    'supports_credentials' => false,

];
