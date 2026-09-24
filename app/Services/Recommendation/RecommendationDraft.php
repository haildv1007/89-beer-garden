<?php

namespace App\Services\Recommendation;

use App\Enums\RecommendationSource;

final readonly class RecommendationDraft
{
    /** @param list<array{product_id:int, quantity:int, reason:string}> $items */
    public function __construct(
        public array $items,
        public RecommendationSource $source,
        public ?string $summary = null,
    ) {}
}
