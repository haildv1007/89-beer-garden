<?php

namespace App\Services\AIChat;

enum AIChatIntent: string
{
    case Greeting = 'greeting';
    case RestaurantInfo = 'restaurant_info';
    case OpeningHours = 'opening_hours';
    case ContactInfo = 'contact_info';
    case MenuSearch = 'menu_search';
    case ProductQuestion = 'product_question';
    case Recommendation = 'recommendation';
    case OrderingGuide = 'ordering_guide';
    case ReservationGuide = 'reservation_guide';
    case PickupGuide = 'pickup_guide';
    case DeliveryGuide = 'delivery_guide';
    case PaymentGuide = 'payment_guide';
    case VoucherGuide = 'voucher_guide';
    case CartQuestion = 'cart_question';
    case AccountHelp = 'account_help';
    case ReservationLookup = 'reservation_lookup';
    case OrderLookup = 'order_lookup';
    case Unsupported = 'unsupported';
}
