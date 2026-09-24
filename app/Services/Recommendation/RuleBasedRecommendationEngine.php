<?php

namespace App\Services\Recommendation;

use App\Enums\RecommendationSource;
use App\Models\Product;

class RuleBasedRecommendationEngine
{
    public function recommend(RecommendationContext $context, RecommendationInput $input): RecommendationDraft
    {
        if ($context->products->isEmpty()) {
            return new RecommendationDraft([], RecommendationSource::Popular);
        }

        $cartGroups = $context->products
            ->whereIn('id', array_keys($context->cartQuantities))
            ->map(fn (Product $product): string => $this->categoryGroup($product))
            ->unique()
            ->values()
            ->all();

        $candidates = $context->products
            ->map(fn (Product $product): array => $this->candidate($product, $context, $input, $cartGroups))
            ->all();

        $hasRecommendationSignals = collect($candidates)->contains(
            fn (array $candidate): bool => $candidate['preference_score'] > 0 || $candidate['time_score'] > 0,
        ) ||
            $context->historyScores !== [] ||
            $context->cartQuantities !== [];
        $source = $hasRecommendationSignals ? RecommendationSource::RuleBased : RecommendationSource::Popular;

        usort($candidates, $source === RecommendationSource::Popular
            ? $this->popularComparator(...)
            : $this->scoreComparator(...));

        $selected = $this->selectBalanced($candidates, $this->targetCount($input->partySize));
        $selected = $this->fitBudget($selected, $candidates, $input);

        return new RecommendationDraft(
            array_map(
                fn (array $candidate): array => [
                    'product_id' => $candidate['product']->id,
                    'quantity' => $candidate['quantity'],
                    'reason' => $source === RecommendationSource::Popular
                        ? __('recommendation.reasons.popular')
                        : $candidate['reason'],
                ],
                $selected,
            ),
            $source,
        );
    }

    /** @param list<string> $cartGroups @return array<string, mixed> */
    private function candidate(
        Product $product,
        RecommendationContext $context,
        RecommendationInput $input,
        array $cartGroups,
    ): array {
        $group = $this->categoryGroup($product);
        $popularity = $context->popularityScores[$product->id] ?? 0;
        $history = $context->historyScores[$product->id] ?? 0;
        $preference = 0;
        foreach ($input->preferences as $token) {
            $expectedGroup = config("recommendation.preferences.{$token}");
            if (($token === 'popular' && $popularity > 0) || ($expectedGroup !== null && $expectedGroup === $group)) {
                $preference += 40;
            }
        }
        $preference = min(80, $preference);
        $time = (int) config("recommendation.time_affinity.{$context->timeBucket}.{$group}", 0);
        $complement = $this->isComplement($cartGroups, $group) ? 15 : 0;
        $alreadyInCart = isset($context->cartQuantities[$product->id]);
        $quantity = $this->quantity($group, $input->partySize);
        $lineTotal = $product->price <= intdiv(PHP_INT_MAX, $quantity)
            ? $product->price * $quantity
            : PHP_INT_MAX;

        return [
            'product' => $product,
            'group' => $group,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'popularity' => $popularity,
            'preference_score' => $preference,
            'time_score' => $time,
            'score' => $preference + $popularity + $time + $history + $complement - ($alreadyInCart ? 100 : 0),
            'reason' => match (true) {
                $preference > 0 => __('recommendation.reasons.preference'),
                $history > 0 => __('recommendation.reasons.history'),
                $complement > 0 => __('recommendation.reasons.cart_pairing'),
                $popularity > 0 => __('recommendation.reasons.popular'),
                $time > 0 => __('recommendation.reasons.time'),
                default => __('recommendation.reasons.balanced'),
            },
        ];
    }

    /** @param list<array<string, mixed>> $candidates @return list<array<string, mixed>> */
    private function selectBalanced(array $candidates, int $target): array
    {
        if ($candidates === []) {
            return [];
        }

        $selected = [];
        $selectedIds = [];
        $categoryCounts = [];
        $beverages = (array) config('recommendation.beverage_groups', []);
        $firstFood = collect($candidates)->first(fn (array $item): bool => ! in_array($item['group'], $beverages, true));
        $firstBeverage = collect($candidates)->first(fn (array $item): bool => in_array($item['group'], $beverages, true));
        foreach ([$firstFood, $firstBeverage] as $candidate) {
            if (is_array($candidate) && count($selected) < $target) {
                $this->appendCandidate($selected, $selectedIds, $categoryCounts, $candidate);
            }
        }

        $byCategory = [];
        foreach ($candidates as $candidate) {
            $byCategory[$candidate['product']->category_id][] = $candidate;
        }
        for ($round = 0; $round < 2 && count($selected) < $target; $round++) {
            foreach ($byCategory as $categoryCandidates) {
                $candidate = $categoryCandidates[$round] ?? null;
                if (is_array($candidate) && ! isset($selectedIds[$candidate['product']->id])) {
                    $this->appendCandidate($selected, $selectedIds, $categoryCounts, $candidate);
                    if (count($selected) >= $target) {
                        break 2;
                    }
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (count($selected) >= $target) {
                break;
            }
            if (! isset($selectedIds[$candidate['product']->id])) {
                $selected[] = $candidate;
                $selectedIds[$candidate['product']->id] = true;
            }
        }

        return $selected;
    }

    /**
     * @param  list<array<string, mixed>>  $selected
     * @param  array<int, true>  $selectedIds
     * @param  array<int, int>  $categoryCounts
     * @param  array<string, mixed>  $candidate
     */
    private function appendCandidate(array &$selected, array &$selectedIds, array &$categoryCounts, array $candidate): void
    {
        $categoryId = (int) $candidate['product']->category_id;
        if (($categoryCounts[$categoryId] ?? 0) >= 2) {
            return;
        }
        $selected[] = $candidate;
        $selectedIds[$candidate['product']->id] = true;
        $categoryCounts[$categoryId] = ($categoryCounts[$categoryId] ?? 0) + 1;
    }

    /**
     * @param  list<array<string, mixed>>  $selected
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private function fitBudget(array $selected, array $candidates, RecommendationInput $input): array
    {
        if ($input->budget === null || $this->total($selected) <= $input->budget || count($candidates) < 3) {
            return $selected;
        }

        usort($candidates, function (array $left, array $right): int {
            return $left['line_total'] <=> $right['line_total']
                ?: $right['score'] <=> $left['score']
                ?: $left['product']->category->sort_order <=> $right['product']->category->sort_order
                ?: $left['product']->id <=> $right['product']->id;
        });
        $sameSize = $this->selectBalanced($candidates, count($selected));
        if ($this->total($sameSize) <= $input->budget) {
            return $sameSize;
        }

        return $this->selectBalanced($candidates, 3);
    }

    /** @param list<array<string, mixed>> $items */
    private function total(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            if ($item['line_total'] > PHP_INT_MAX - $total) {
                return PHP_INT_MAX;
            }
            $total += $item['line_total'];
        }

        return $total;
    }

    /** @param list<string> $cartGroups */
    private function isComplement(array $cartGroups, string $candidateGroup): bool
    {
        foreach ($cartGroups as $cartGroup) {
            if (in_array($candidateGroup, (array) config("recommendation.complements.{$cartGroup}", []), true)) {
                return true;
            }
        }

        return false;
    }

    private function categoryGroup(Product $product): string
    {
        $slug = strtolower((string) $product->category->slug);
        foreach ((array) config('recommendation.category_groups', []) as $group => $needles) {
            foreach ((array) $needles as $needle) {
                if ($needle !== '' && str_contains($slug, strtolower((string) $needle))) {
                    return (string) $group;
                }
            }
        }

        return 'other';
    }

    private function quantity(string $group, int $partySize): int
    {
        $quantity = match (true) {
            in_array($group, (array) config('recommendation.beverage_groups', []), true) => $partySize,
            $group === 'hotpot' => (int) ceil($partySize / 6),
            default => (int) ceil($partySize / 4),
        };

        return max(1, min((int) config('recommendation.limits.max_quantity', 50), $quantity));
    }

    private function targetCount(int $partySize): int
    {
        return match (true) {
            $partySize <= 2 => 3,
            $partySize <= 5 => 4,
            $partySize <= 10 => 5,
            default => 6,
        };
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function scoreComparator(array $left, array $right): int
    {
        return $right['score'] <=> $left['score']
            ?: $left['product']->category->sort_order <=> $right['product']->category->sort_order
            ?: $left['product']->id <=> $right['product']->id;
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function popularComparator(array $left, array $right): int
    {
        return $right['popularity'] <=> $left['popularity']
            ?: $left['product']->category->sort_order <=> $right['product']->category->sort_order
            ?: $left['product']->id <=> $right['product']->id;
    }
}
