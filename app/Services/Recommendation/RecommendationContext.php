<?php

namespace App\Services\Recommendation;

use App\Models\Product;
use Illuminate\Support\Collection;

final readonly class RecommendationContext
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  array<int, int>  $popularityScores
     * @param  array<int, int>  $historyScores
     * @param  array<int, int>  $cartQuantities
     */
    public function __construct(
        public Collection $products,
        public array $popularityScores,
        public array $historyScores,
        public array $cartQuantities,
        public string $timeBucket,
    ) {}
}
