<?php

namespace App\Services\Recommendation;

final readonly class RecommendationProviderInput
{
    /**
     * @param  list<string>  $preferences
     * @param  list<array<string, mixed>>  $catalog
     * @param  list<array{product_id:int, quantity:int}>  $cart
     * @param  list<array{product_id:int, score:int}>  $history
     */
    public function __construct(
        public string $locale,
        public int $partySize,
        public ?int $budget,
        public array $preferences,
        public ?string $note,
        public string $timeBucket,
        public array $catalog,
        public array $cart,
        public array $history,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'party_size' => $this->partySize,
            'budget' => $this->budget,
            'preferences' => $this->preferences,
            'note' => $this->note,
            'time_bucket' => $this->timeBucket,
            'products' => $this->catalog,
            'current_cart' => $this->cart,
            'customer_history' => $this->history,
        ];
    }
}
