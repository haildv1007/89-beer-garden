<?php

return [
    'title' => 'System Configuration',
    'intro' => 'Only approved runtime settings can be changed here. Secrets and environment configuration are not managed through this interface.',
    'valid' => 'Valid',
    'invalid' => 'Invalid',
    'type' => 'Type',
    'current_value' => 'Current value',
    'updated_by' => 'Updated by',
    'updated_at' => 'Updated at',
    'new_value' => 'New value',
    'missing' => 'Not configured',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'save' => 'Save setting',
    'saved' => 'Setting saved.',
    'types' => ['integer' => 'Integer', 'boolean' => 'Boolean', 'string' => 'Text'],
    'items' => [
        'no_show_timeout_minutes' => [
            'label' => 'No-show timeout',
            'description' => 'Minutes after the reservation time before a confirmed reservation may be marked no-show (1–1440 minutes).',
        ],
        'customer_ordering_enabled' => [
            'label' => 'Customer self-ordering',
            'description' => 'When disabled, signed table links, carts and the customer self-order workflow cannot be used.',
        ],
        'delivery_fee' => [
            'label' => 'Delivery fee',
            'description' => 'Default delivery charge in VND (0–10,000,000). Existing orders retain the fee captured when placed.',
        ],
        'vietqr_bank_id' => ['label' => 'VietQR bank', 'description' => 'Bank code such as MB, VCB or BIDV.'],
        'vietqr_account_number' => [
            'label' => 'Receiving account number',
            'description' => 'Restaurant bank account number.',
        ],
        'vietqr_account_name' => [
            'label' => 'Account holder',
            'description' => 'Account holder name displayed with the QR.',
        ],
        'vietqr_transfer_prefix' => [
            'label' => 'Transfer prefix',
            'description' => 'Short prefix combined with the order code.',
        ],
    ],
    'validation' => [
        'invalid' => 'The setting value is invalid or the key is not allowed.',
        'actor_inactive' => 'The account or employee profile is no longer active.',
    ],
];
