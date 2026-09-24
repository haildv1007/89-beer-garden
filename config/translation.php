<?php

return [
    'provider' => env('TRANSLATION_PROVIDER', 'null'),
    'google' => [
        'project_id' => env('GOOGLE_TRANSLATION_PROJECT_ID'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'timeout_seconds' => (float) env('GOOGLE_TRANSLATION_TIMEOUT_SECONDS', 3),
        'max_calls_per_request' => (int) env('GOOGLE_TRANSLATION_MAX_CALLS_PER_REQUEST', 10),
    ],
];
