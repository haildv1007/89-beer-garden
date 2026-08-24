<?php

return [
    'people' => ':count guests',
    'fields' => ['name' => 'Name', 'phone' => 'Phone', 'date' => 'Reservation date', 'time' => 'Reservation time', 'date_time' => 'Date and time', 'party_size' => 'Party size', 'note' => 'Note', 'code' => 'Reservation code', 'customer' => 'Customer', 'status' => 'Status', 'table' => 'Table'],
    'statuses' => ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked-in' => 'Checked in', 'completed' => 'Completed', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'no-show' => 'No-show'],
    'validation' => ['future_time' => 'The reservation date and time must be in the future.', 'date_range' => 'The end date must be on or after the start date.'],
    'errors' => [
        'pending_required' => 'Only a pending reservation request can perform this operation.',
        'confirmed_required' => 'Only a confirmed reservation can be marked as no-show.',
        'no_capacity' => 'No active table can accommodate this party size.',
        'no_show_setting_missing' => 'No valid no-show setting exists. Configure no_show_timeout_minutes first.',
        'no_show_too_early' => 'This reservation cannot be marked as no-show yet.',
    ],
    'customer' => [
        'make' => 'Reserve a table', 'mine' => 'My reservations', 'submit' => 'Submit reservation request',
        'no_table_selection' => 'Choose a time and party size; the restaurant will arrange a suitable table.',
        'created' => 'Your reservation request has been recorded.', 'confirmation_title' => 'Reservation request received',
        'confirmation_message' => 'Please keep the code below. The restaurant will review and confirm your request.',
        'empty' => 'You have no reservation requests.', 'back' => 'Back to reservations',
        'table_not_assigned' => 'Not assigned', 'processing_notice' => 'A reservation does not open a dining session. Staff will handle check-in and table assignment when you arrive.',
    ],
    'internal' => [
        'title' => 'Reservation management', 'search_placeholder' => 'Search code, name, or phone',
        'all_statuses' => 'All statuses', 'date_from' => 'From date', 'date_to' => 'To date',
        'empty' => 'No matching reservation requests.', 'back' => 'Back to list',
        'not_assigned' => 'Not assigned', 'confirmed_by' => 'Confirmed by',
        'confirm' => 'Confirm', 'reject' => 'Reject', 'mark_no_show' => 'Mark no-show',
        'confirmed' => 'The reservation request has been confirmed.', 'rejected' => 'The reservation request has been rejected.',
        'marked_no_show' => 'The reservation has been marked as no-show.',
        'check_in_later' => 'Check-in, table assignment, and opening a Dining Session will be implemented in Phase 4.8.',
    ],
];
