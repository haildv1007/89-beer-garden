<?php

return [
    'ai_chat' => env('FEATURE_AI_CHAT', false),
    'recommendation' => env('FEATURE_RECOMMENDATION', false),

    /*
    |--------------------------------------------------------------------------
    | Inventory management
    |--------------------------------------------------------------------------
    |
    | Inventory is outside the current deployment scope. Keep the dormant
    | module available for a future rollout without exposing its routes or UI.
    |
    */
    'inventory' => env('FEATURE_INVENTORY', false),
];
