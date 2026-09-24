<?php

return [
    'provider' => env('AI_CHAT_PROVIDER', 'null'),
    'gemini_enabled' => env('AI_CHAT_GEMINI_ENABLED', false),
    'timeout_seconds' => (float) env('AI_CHAT_TIMEOUT_SECONDS', 15),
    'limits' => [
        'messages' => 20,
        'context_characters' => 12000,
        'reply_characters' => 1500,
        'products' => 8,
        'customer_records' => 5,
        'authenticated_per_minute' => 30,
        'guest_per_minute' => 20,
    ],
];
