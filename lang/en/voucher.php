<?php

return [
    'title' => 'Vouchers', 'create' => 'Create voucher', 'search' => 'Search code or name', 'all_statuses' => 'All statuses',
    'empty' => 'No vouchers yet.', 'historical_locked' => 'This voucher is linked to a bill and its historical data is locked.',
    'fields' => ['code' => 'Code', 'name' => 'Name', 'type' => 'Discount type', 'value' => 'Value', 'max_discount' => 'Maximum discount', 'minimum' => 'Minimum order', 'start' => 'Starts at', 'end' => 'Ends at', 'usage_limit' => 'Usage limit', 'used' => 'Used', 'status' => 'Status'],
    'types' => ['fixed' => 'Fixed amount', 'percentage' => 'Percentage (0–100)'],
    'statuses' => ['active' => 'Active', 'inactive' => 'Inactive'],
    'errors' => ['historical' => 'A voucher linked to a bill cannot be edited or deleted.'],
];
