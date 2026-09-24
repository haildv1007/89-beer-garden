<?php

namespace App\Services\Recommendation;

final readonly class RecommendationInput
{
    /** @param list<string> $preferences */
    public function __construct(
        public int $partySize,
        public ?int $budget,
        public array $preferences,
        public ?string $note,
    ) {}
}
