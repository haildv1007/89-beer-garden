<?php

return [
    'ai_enabled' => env('RECOMMENDATION_AI_ENABLED', false),
    'provider' => env('RECOMMENDATION_PROVIDER', 'null'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'timeout_seconds' => (float) env('GEMINI_TIMEOUT_SECONDS', 2.5),
    ],

    'limits' => [
        'max_items' => 6,
        'max_quantity' => 50,
        'popularity_days' => 90,
        'history_days' => 180,
        'history_products' => 20,
        'provider_catalog' => 100,
    ],

    'preferences' => [
        'popular' => null,
        'beer' => 'beer',
        'non_alcoholic' => 'non_alcoholic',
        'appetizer' => 'appetizer',
        'grilled' => 'grilled',
        'seafood' => 'seafood',
        'hotpot' => 'hotpot',
    ],

    'category_groups' => [
        'beer' => ['beer', 'bia', 'do-uong-co-con'],
        'non_alcoholic' => ['non-alcoholic', 'soft-drink', 'nuoc-giai-khat', 'khong-con'],
        'appetizer' => ['appetizer', 'starter', 'khai-vi'],
        'grilled' => ['grilled', 'grill', 'mon-nuong', 'do-nuong'],
        'seafood' => ['seafood', 'hai-san'],
        'hotpot' => ['hotpot', 'lau'],
    ],

    'beverage_groups' => ['beer', 'non_alcoholic'],
    'complements' => [
        'beer' => ['appetizer', 'grilled', 'seafood'],
        'grilled' => ['beer', 'non_alcoholic'],
        'seafood' => ['beer', 'non_alcoholic'],
        'hotpot' => ['appetizer', 'beer', 'non_alcoholic'],
    ],
    'time_affinity' => [
        'morning' => ['appetizer' => 5, 'non_alcoholic' => 5],
        'lunch' => ['appetizer' => 5, 'grilled' => 5, 'seafood' => 5, 'hotpot' => 5],
        'afternoon' => ['appetizer' => 5, 'non_alcoholic' => 5],
        'evening' => ['beer' => 10, 'grilled' => 10, 'seafood' => 10, 'hotpot' => 10],
        'late_night' => ['beer' => 5, 'appetizer' => 5, 'grilled' => 5],
    ],
];
