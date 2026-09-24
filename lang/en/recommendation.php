<?php

return [
    'validation' => [
        'unknown_field' => 'The request contains an unsupported field.',
    ],
    'summary' => [
        'rule_based' => 'This set balances your preferences, party size, and available data.',
        'popular' => 'Popular items that are currently available.',
        'over_budget' => 'This is the closest suitable set, but its estimated total exceeds your budget.',
        'empty' => 'No suitable set is available right now. You can still choose from the menu.',
    ],
    'reasons' => [
        'preference' => 'Matches your selected preferences.',
        'history' => 'Matches your previous choices.',
        'cart_pairing' => 'Pairs well with items already in your cart.',
        'popular' => 'Frequently chosen by guests recently.',
        'time' => 'A good fit for the current time.',
        'balanced' => 'Adds balance to the suggested set.',
    ],
    'cart' => [
        'added' => 'The full suggested set was added to your cart.',
        'set_invalid' => 'This suggested set is no longer valid. Please generate a new recommendation.',
        'product_unavailable' => 'One or more items are no longer available. Please generate a new recommendation or add the remaining items individually.',
        'quantity_exceeded' => 'The resulting cart quantity exceeds the allowed limit.',
    ],
];
