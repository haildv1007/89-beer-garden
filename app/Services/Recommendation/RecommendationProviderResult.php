<?php

namespace App\Services\Recommendation;

final readonly class RecommendationProviderResult
{
    private function __construct(
        public bool $available,
        public ?string $content,
        public ?string $failureCategory,
        public ?int $httpStatus,
        public int $durationMs,
    ) {}

    public static function success(string $content, int $durationMs): self
    {
        return new self(true, $content, null, null, $durationMs);
    }

    public static function unavailable(
        string $category,
        int $durationMs = 0,
        ?int $httpStatus = null,
    ): self {
        return new self(false, null, $category, $httpStatus, $durationMs);
    }
}
