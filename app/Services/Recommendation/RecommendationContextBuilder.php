<?php

namespace App\Services\Recommendation;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationContextBuilder
{
    /** @param list<array{product_id:int, quantity:int}> $cartItems */
    public function build(?User $user = null, array $cartItems = []): RecommendationContext
    {
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->with('category:id,name,slug,status,sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'category_id',
                'name',
                'slug',
                'short_description',
                'description',
                'price',
                'status',
                'is_available',
            ]);

        $productIds = $products->modelKeys();

        return new RecommendationContext(
            products: $products,
            popularityScores: $this->popularityScores($productIds),
            historyScores: $this->historyScores($user, $productIds),
            cartQuantities: $this->cartQuantities($cartItems, $products),
            timeBucket: $this->timeBucket(CarbonImmutable::now(config('app.timezone'))),
        );
    }

    public function providerInput(
        RecommendationContext $context,
        RecommendationInput $input,
        string $locale,
    ): RecommendationProviderInput {
        $catalogLimit = (int) config('recommendation.limits.provider_catalog', 100);
        $products = $context->products
            ->sort(function (Product $left, Product $right) use ($context): int {
                return ($context->popularityScores[$right->id] ?? 0) <=>
                    ($context->popularityScores[$left->id] ?? 0)
                    ?: $left->id <=> $right->id;
            })
            ->take($catalogLimit)
            ->values();
        $catalogIds = array_fill_keys($products->modelKeys(), true);

        return new RecommendationProviderInput(
            locale: $locale,
            partySize: $input->partySize,
            budget: $input->budget,
            preferences: $input->preferences,
            note: $input->note,
            timeBucket: $context->timeBucket,
            catalog: $products
                ->map(fn (Product $product): array => [
                    'product_id' => $product->id,
                    'category_slug' => $product->category->slug,
                    'name' => $product->name,
                    'description' => mb_substr(
                        (string) ($product->short_description ?: $product->description),
                        0,
                        300,
                    ),
                    'price' => $product->price,
                    'popularity_score' => $context->popularityScores[$product->id] ?? 0,
                ])
                ->all(),
            cart: collect($context->cartQuantities)
                ->filter(fn (int $quantity, int $productId): bool => isset($catalogIds[$productId]))
                ->map(fn (int $quantity, int $productId): array => [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ])
                ->values()
                ->all(),
            history: collect($context->historyScores)
                ->filter(fn (int $score, int $productId): bool => isset($catalogIds[$productId]))
                ->map(fn (int $score, int $productId): array => [
                    'product_id' => $productId,
                    'score' => $score,
                ])
                ->values()
                ->all(),
        );
    }

    /** @param list<int> $productIds @return array<int, int> */
    private function popularityScores(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('dining_sessions', 'dining_sessions.id', '=', 'orders.dining_session_id')
            ->join('bills', 'bills.dining_session_id', '=', 'dining_sessions.id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.status', '!=', OrderItemStatus::Cancelled->value)
            ->where('dining_sessions.status', DiningSessionStatus::Completed->value)
            ->where('bills.status', BillStatus::Paid->value)
            ->where('orders.ordered_at', '>=', now()->subDays((int) config('recommendation.limits.popularity_days', 90)))
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->orderBy('order_items.product_id')
            ->get([
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) AS total_quantity'),
            ]);

        return $this->rankScores($rows, 25);
    }

    /** @param list<int> $productIds @return array<int, int> */
    private function historyScores(?User $user, array $productIds): array
    {
        if (! $user instanceof User || $productIds === []) {
            return [];
        }
        $customer = $user->customer()->first();
        if ($customer === null) {
            return [];
        }

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('dining_sessions', 'dining_sessions.id', '=', 'orders.dining_session_id')
            ->join('bills', 'bills.dining_session_id', '=', 'dining_sessions.id')
            ->where('dining_sessions.customer_id', $customer->id)
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.status', '!=', OrderItemStatus::Cancelled->value)
            ->where('dining_sessions.status', DiningSessionStatus::Completed->value)
            ->where('bills.status', BillStatus::Paid->value)
            ->where('orders.ordered_at', '>=', now()->subDays((int) config('recommendation.limits.history_days', 180)))
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->orderBy('order_items.product_id')
            ->limit((int) config('recommendation.limits.history_products', 20))
            ->get([
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) AS total_quantity'),
            ]);

        return $this->rankScores($rows, 15);
    }

    /**
     * @param  list<array{product_id:int, quantity:int}>  $cartItems
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    private function cartQuantities(array $cartItems, Collection $products): array
    {
        $eligible = $products->keyBy('id');
        $quantities = [];
        foreach ($cartItems as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);
            if ($quantity > 0 && $eligible->has($productId)) {
                $quantities[$productId] = $quantity;
            }
        }

        return $quantities;
    }

    /** @param Collection<int, object> $rows @return array<int, int> */
    private function rankScores(Collection $rows, int $maximum): array
    {
        $scores = [];
        foreach ($rows->values() as $index => $row) {
            $scores[(int) $row->product_id] = max(1, $maximum - $index);
        }

        return $scores;
    }

    private function timeBucket(CarbonImmutable $time): string
    {
        return match (true) {
            $time->hour >= 5 && $time->hour <= 10 => 'morning',
            $time->hour >= 11 && $time->hour <= 13 => 'lunch',
            $time->hour >= 14 && $time->hour <= 16 => 'afternoon',
            $time->hour >= 17 && $time->hour <= 22 => 'evening',
            default => 'late_night',
        };
    }
}
