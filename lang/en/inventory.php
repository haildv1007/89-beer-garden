<?php

return [
    'title' => 'Inventory', 'create' => 'Create inventory item', 'edit' => 'Edit inventory item',
    'search' => 'Search SKU, name or product', 'all_statuses' => 'All statuses', 'low_stock_only' => 'Low stock only',
    'low_stock' => 'Low stock', 'low_stock_warning' => 'Current stock is at or below minimum stock.',
    'empty' => 'No inventory items yet.', 'no_product' => 'No linked product',
    'stock_via_movement' => 'Stock can only be changed through an auditable stock movement.',
    'create_movement' => 'Create stock movement', 'movement_created' => 'Stock movement recorded.',
    'history' => 'Immutable movement history', 'no_movements' => 'No stock movements yet.',
    'fields' => ['sku' => 'SKU', 'name' => 'Name', 'unit' => 'Unit', 'minimum_stock' => 'Minimum stock',
        'current_stock' => 'Current stock', 'status' => 'Status', 'product' => 'Linked product',
        'time' => 'Time', 'type' => 'Type', 'quantity' => 'Quantity', 'before' => 'Stock before',
        'after' => 'Stock after', 'actor' => 'Actor', 'note' => 'Note'],
    'statuses' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'types' => ['import' => 'Import', 'export' => 'Export', 'adjustment_in' => 'Adjustment in',
        'adjustment_out' => 'Adjustment out', 'damaged' => 'Damaged', 'return' => 'Return to stock'],
    'errors' => ['item_inactive' => 'The inventory item is deleted or inactive.',
        'actor_inactive' => 'The acting account is no longer active.',
        'overflow' => 'The resulting stock exceeds the supported range.',
        'insufficient' => 'There is not enough stock for this decrease.'],
];
