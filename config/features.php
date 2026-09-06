<?php

return [
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
