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

    'paths' => ['*'],

    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5175',
        'http://localhost:8081',
        'http://127.0.0.1:8081',
        'http://localhost:8082',
        'http://127.0.0.1:8082',
        'http://localhost:8083',
        'http://127.0.0.1:8083',
        'http://103.158.196.79',
        'https://nl-siak.uika-bogor.ac.id',
        'https://nl-simpeg.uika-bogor.ac.id',
        'https://ucl.uika-bogor.ac.id',
        'https://api-simpeg.uika-bogor.ac.id',
        'https://api-tias.ti.ft.uika-bogor.ac.id',
        'https://u-talent.uika-bogor.ac.id',
        'https://eportal.uika-bogor.ac.id',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
