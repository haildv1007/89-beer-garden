<?php

namespace App\Services\Recommendation;

use App\Enums\RecommendationSource;
use JsonException;

class ProviderOutputNormalizer
{
    private const FORBIDDEN_FIELDS = [
        'price',
        'unit_price',
        'line_total',
        'estimated_total',
        'product_name',
        'category',
        'source',
        'availability',
        'is_available',
        'customer_id',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'google_id',
        'session_id',
        'order_id',
        'order_code',
        'voucher',
        'discount',
        'payment',
    ];

    public function normalize(
        RecommendationProviderResult $providerResult,
        RecommendationProviderInput $providerInput,
    ): NormalizedProviderResult {
        if (! $providerResult->available || $providerResult->content === null) {
            return NormalizedProviderResult::invalid($providerResult->failureCategory ?? 'provider_unavailable');
        }

        try {
            $payload = json_decode($providerResult->content, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return NormalizedProviderResult::invalid('invalid_json');
        }
        if (! is_array($payload) || ! isset($payload['items']) || ! is_array($payload['items'])) {
            return NormalizedProviderResult::invalid('invalid_schema');
        }
        if (array_intersect(array_keys($payload), self::FORBIDDEN_FIELDS) !== []) {
            return NormalizedProviderResult::invalid('forbidden_field');
        }
        if (count($payload['items']) < 1 || count($payload['items']) > 6) {
            return NormalizedProviderResult::invalid('invalid_item_count');
        }
        $summary = $payload['summary'] ?? '';
        if (! is_string($summary) || trim($summary) === '' || mb_strlen($summary) > 500) {
            return NormalizedProviderResult::invalid('invalid_summary');
        }

        $eligibleIds = array_fill_keys(array_column($providerInput->catalog, 'product_id'), true);
        $seen = [];
        $items = [];
        foreach ($payload['items'] as $item) {
            if (! is_array($item)) {
                return NormalizedProviderResult::invalid('invalid_item');
            }
            if (array_intersect(array_keys($item), self::FORBIDDEN_FIELDS) !== []) {
                return NormalizedProviderResult::invalid('forbidden_field');
            }
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? null;
            $reason = $item['reason'] ?? null;
            if (! is_int($productId) || ! isset($eligibleIds[$productId])) {
                return NormalizedProviderResult::invalid('unknown_product');
            }
            if (isset($seen[$productId])) {
                return NormalizedProviderResult::invalid('duplicate_product');
            }
            if (! is_int($quantity) || $quantity < 1 || $quantity > 50) {
                return NormalizedProviderResult::invalid('invalid_quantity');
            }
            if (! is_string($reason) || trim($reason) === '' || mb_strlen($reason) > 160) {
                return NormalizedProviderResult::invalid('invalid_reason');
            }
            $seen[$productId] = true;
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'reason' => trim($reason),
            ];
        }

        return NormalizedProviderResult::success(new RecommendationDraft(
            items: $items,
            source: RecommendationSource::Ai,
            summary: trim($summary),
        ));
    }
}
