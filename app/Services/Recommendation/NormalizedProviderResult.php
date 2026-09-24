<?php

namespace App\Services\Recommendation;

final readonly class NormalizedProviderResult
{
    private function __construct(
        public ?RecommendationDraft $draft,
        public ?string $failureCategory,
    ) {}

    public static function success(RecommendationDraft $draft): self
    {
        return new self($draft, null);
    }

    public static function invalid(string $category): self
    {
        return new self(null, $category);
    }
}
