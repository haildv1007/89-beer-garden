<?php

return [
    'title' => 'Kitchen Queue',
    'refresh' => 'Refresh',
    'manual_refresh_note' => 'Manual refresh keeps the kitchen queue predictable.',
    'wait_minutes' => ':count min',
    'empty' => 'No items in this section.',
    'quantity' => 'Quantity: :count',
    'actions' => 'Actions',
    'start_preparing' => 'Start preparing',
    'mark_ready' => 'Mark ready',
    'mark_served' => 'Mark served',
    'cancel_item' => 'Cancel item',
    'cancellation_reason' => 'Cancellation reason',
    'started' => 'Item is now preparing.',
    'ready' => 'Item is now ready.',
    'served' => 'Item marked served.',
    'cancelled' => 'Item cancelled.',
    'sections' => ['waiting' => 'Waiting', 'preparing' => 'Preparing', 'ready' => 'Ready for service'],
    'errors' => [
        'transition_invalid' => 'The item or dining session state is no longer valid for this action.',
        'cancellation_invalid' => 'The item is no longer cancellable or its dining session has ended.',
    ],
];
