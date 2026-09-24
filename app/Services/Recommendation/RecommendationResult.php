<?php

namespace App\Services\Recommendation;

use App\Enums\RecommendationBudgetStatus;
use App\Enums\RecommendationSource;

final readonly class RecommendationResult
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public array $items,
        public string $summary,
        public int $estimatedTotal,
        public ?int $budget,
        public RecommendationBudgetStatus $budgetStatus,
        public RecommendationSource $source,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'summary' => $this->summary,
            'estimated_total' => $this->estimatedTotal,
            'budget' => $this->budget,
            'budget_status' => $this->budgetStatus->value,
            'source' => $this->source->value,
        ];
    }
}
