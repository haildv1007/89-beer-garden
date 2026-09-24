<?php

namespace App\Services\AIChat;

class SupportKnowledgeCatalog
{
    public function answer(AIChatIntent $intent, ?int $deliveryFee = null, bool $vietQrAvailable = false): string
    {
        $key = match ($intent) {
            AIChatIntent::OrderingGuide => 'ordering',
            AIChatIntent::ReservationGuide => 'reservation',
            AIChatIntent::PickupGuide => 'pickup',
            AIChatIntent::DeliveryGuide => 'delivery',
            AIChatIntent::PaymentGuide => $vietQrAvailable ? 'payment_vietqr' : 'payment',
            AIChatIntent::VoucherGuide => 'voucher',
            AIChatIntent::AccountHelp => 'account',
            default => 'unsupported',
        };

        return __('ai_chat.knowledge.'.$key, [
            'delivery_fee' => $deliveryFee === null ? __('ai_chat.not_configured') : number_format($deliveryFee).' VND',
        ]);
    }
}
