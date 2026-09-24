<?php

namespace App\Services\AIChat;

final class AIChatCatalog
{
    public const TOOLS = [
        'restaurant_information',
        'menu_search',
        'product_lookup',
        'recommendation',
        'ordering_guide',
        'payment_guide',
        'cart_summary',
        'reservation_lookup',
        'order_lookup',
        'account_help',
        'handoff',
    ];

    public const ACTIONS = [
        'view_menu',
        'view_product',
        'view_cart',
        'add_product_to_cart',
        'add_recommendation_set_to_cart',
        'make_reservation',
        'view_reservations',
        'view_orders',
        'login',
        'open_contact',
        'open_map',
        'call_hotline',
        'new_conversation',
    ];
}
