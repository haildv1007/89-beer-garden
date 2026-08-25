<?php

return [
    'create' => 'Create order', 'additional' => 'Additional order', 'submit' => 'Create order',
    'created' => 'Order created.', 'item_updated' => 'Waiting item updated.',
    'history' => 'Order history', 'empty' => 'No orders yet.', 'empty_products' => 'No products are currently valid for ordering.', 'search_products' => 'Search product or category',
    'subtotal' => 'Display subtotal',
    'fields' => ['product' => 'Product', 'price' => 'Historical unit price', 'quantity' => 'Quantity', 'line_total' => 'Line total', 'status' => 'Status', 'order_note' => 'Order note', 'item_note' => 'Item note'],
    'sources' => ['staff' => 'POS', 'customer' => 'Customer'],
    'statuses' => ['waiting' => 'Waiting', 'preparing' => 'Preparing', 'ready' => 'Ready', 'served' => 'Served', 'cancelled' => 'Cancelled'],
    'errors' => ['session_invalid' => 'The dining session or table state is no longer valid for ordering.', 'product_invalid' => 'One or more products are inactive, unavailable, deleted, or belong to an inactive category.', 'waiting_required' => 'Only a waiting item in an active session can be updated.', 'quantity_overflow' => 'The quantity makes the line total exceed the storage limit.'],
];
