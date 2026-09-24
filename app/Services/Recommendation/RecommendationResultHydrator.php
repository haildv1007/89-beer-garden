<?php

namespace App\Services\Recommendation;

use App\Enums\RecommendationBudgetStatus;
use App\Enums\RecommendationSource;
use App\Models\Product;

class RecommendationResultHydrator
{
    public function hydrate(RecommendationDraft $draft, RecommendationInput $input): RecommendationResult
    {
        $draftItems = collect($draft->items)
            ->unique('product_id')
            ->take((int) config('recommendation.limits.max_items', 6))
            ->values();
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->whereIn('id', $draftItems->pluck('product_id'))
            ->with([
                'category:id,name,slug,status,sort_order',
                'media:id,product_id,path,media_type,mime_type,original_name,sort_order',
                'availableVariants',
            ])
            ->get([
                'id',
                'category_id',
                'name',
                'slug',
                'price',
                'image_url',
                'status',
                'is_available',
            ])
            ->keyBy('id');

        $items = [];
        $estimatedTotal = 0;
        foreach ($draftItems as $draftItem) {
            $product = $products->get((int) $draftItem['product_id']);
            if (! $product instanceof Product) {
                continue;
            }
            $variant = $product->availableVariants->first();
            $unitPrice = $variant?->price ?? $product->price;
            $quantity = max(
                1,
                min((int) config('recommendation.limits.max_quantity', 50), (int) $draftItem['quantity']),
            );
            if ($unitPrice > intdiv(PHP_INT_MAX, $quantity)) {
                continue;
            }
            $lineTotal = $unitPrice * $quantity;
            if ($lineTotal > PHP_INT_MAX - $estimatedTotal) {
                continue;
            }
            $estimatedTotal += $lineTotal;
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->primary_image_url,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],
                'variant_id' => $variant?->id,
                'variant_name' => $variant?->name,
                'price' => $unitPrice,
                'variants' => $product->availableVariants->map->only(['id', 'name', 'price'])->values()->all(),
                'availability' => true,
                'quantity' => $quantity,
                'reason' => mb_substr((string) $draftItem['reason'], 0, 160),
                'line_total' => $lineTotal,
            ];
        }

        $source = $items === [] ? RecommendationSource::Popular : $draft->source;
        $budgetStatus = match (true) {
            $input->budget === null => RecommendationBudgetStatus::NotProvided,
            $estimatedTotal <= $input->budget => RecommendationBudgetStatus::WithinBudget,
            default => RecommendationBudgetStatus::OverBudget,
        };
        $summaryKey = match (true) {
            $items === [] => 'recommendation.summary.empty',
            $budgetStatus === RecommendationBudgetStatus::OverBudget => 'recommendation.summary.over_budget',
            $source === RecommendationSource::Popular => 'recommendation.summary.popular',
            default => 'recommendation.summary.rule_based',
        };
        $summary = $source === RecommendationSource::Ai && $draft->summary !== null
            ? mb_substr($draft->summary, 0, 500)
            : __($summaryKey);

        return new RecommendationResult(
            items: $items,
            summary: $summary,
            estimatedTotal: $estimatedTotal,
            budget: $input->budget,
            budgetStatus: $budgetStatus,
            source: $source,
        );
    }
}
