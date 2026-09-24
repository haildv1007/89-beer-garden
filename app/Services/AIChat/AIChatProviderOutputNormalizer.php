<?php

namespace App\Services\AIChat;

use JsonException;

class AIChatProviderOutputNormalizer
{
    public function normalize(AIChatProviderResult $result): NormalizedAIChatProviderResult
    {
        if (! $result->available || $result->content === null) {
            return NormalizedAIChatProviderResult::invalid($result->failureCategory ?? 'provider_unavailable');
        }
        try {
            $payload = json_decode($result->content, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return NormalizedAIChatProviderResult::invalid('invalid_json');
        }
        if (! is_array($payload)) {
            return NormalizedAIChatProviderResult::invalid('invalid_schema');
        }
        $intent = is_string($payload['intent'] ?? null)
            ? AIChatIntent::tryFrom($payload['intent'])
            : null;
        $reply = $payload['reply'] ?? null;
        $tool = $payload['requested_tool'] ?? null;
        if (
            $intent === null ||
            ! is_string($reply) || trim($reply) === '' || mb_strlen($reply) > 1500 ||
            ($tool !== null && (! is_string($tool) || ! in_array($tool, AIChatCatalog::TOOLS, true))) ||
            ($tool !== null && ! in_array($tool, $this->toolsFor($intent), true))
        ) {
            return NormalizedAIChatProviderResult::invalid('invalid_schema');
        }
        $context = $this->context($payload['extracted_context'] ?? []);
        if ($context === null) {
            return NormalizedAIChatProviderResult::invalid('invalid_context');
        }
        $productIds = $payload['product_ids'] ?? [];
        if (! is_array($productIds) || count($productIds) > 8) {
            return NormalizedAIChatProviderResult::invalid('invalid_products');
        }
        foreach ($productIds as $id) {
            if (! is_int($id) || $id < 1) {
                return NormalizedAIChatProviderResult::invalid('invalid_products');
            }
        }
        $actions = $payload['suggested_action_types'] ?? [];
        if (! is_array($actions)) {
            return NormalizedAIChatProviderResult::invalid('invalid_actions');
        }
        foreach ($actions as $action) {
            if (! is_string($action) || ! in_array($action, AIChatCatalog::ACTIONS, true)) {
                return NormalizedAIChatProviderResult::invalid('invalid_actions');
            }
        }

        return NormalizedAIChatProviderResult::success(
            $intent,
            mb_substr(trim(strip_tags($reply)), 0, 1500),
            $context,
            $tool,
            array_values(array_unique($productIds)),
            array_values(array_unique($actions)),
        );
    }

    /** @return array<string,mixed>|null */
    private function context(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }
        $context = [];
        if (array_key_exists('party_size', $value)) {
            if (! is_int($value['party_size']) || $value['party_size'] < 1 || $value['party_size'] > 50) {
                return null;
            }
            $context['party_size'] = $value['party_size'];
        }
        if (array_key_exists('budget', $value) && $value['budget'] !== null) {
            if (! is_int($value['budget']) || $value['budget'] < 10000 || $value['budget'] > 100000000) {
                return null;
            }
            $context['budget'] = $value['budget'];
        }
        if (array_key_exists('preferences', $value)) {
            if (! is_array($value['preferences']) || count($value['preferences']) > 5) {
                return null;
            }
            $allowed = array_keys((array) config('recommendation.preferences', []));
            foreach ($value['preferences'] as $preference) {
                if (! is_string($preference) || ! in_array($preference, $allowed, true)) {
                    return null;
                }
            }
            $context['preferences'] = array_values(array_unique($value['preferences']));
        }

        return $context;
    }

    /** @return list<string> */
    private function toolsFor(AIChatIntent $intent): array
    {
        return match ($intent) {
            AIChatIntent::Greeting => [],
            AIChatIntent::RestaurantInfo, AIChatIntent::OpeningHours, AIChatIntent::ContactInfo => ['restaurant_information'],
            AIChatIntent::MenuSearch => ['menu_search'],
            AIChatIntent::ProductQuestion => ['product_lookup'],
            AIChatIntent::Recommendation => ['recommendation'],
            AIChatIntent::OrderingGuide, AIChatIntent::ReservationGuide, AIChatIntent::PickupGuide, AIChatIntent::DeliveryGuide => ['ordering_guide'],
            AIChatIntent::PaymentGuide, AIChatIntent::VoucherGuide => ['payment_guide'],
            AIChatIntent::CartQuestion => ['cart_summary'],
            AIChatIntent::AccountHelp => ['account_help'],
            AIChatIntent::ReservationLookup => ['reservation_lookup'],
            AIChatIntent::OrderLookup => ['order_lookup'],
            AIChatIntent::Unsupported => ['handoff'],
        };
    }
}
