<?php

namespace App\Services\AIChat;

final readonly class AIChatProviderInput
{
    /** @param list<array{role:string, content:string}> $messages */
    public function __construct(
        public string $locale,
        public array $messages,
        public array $businessContext = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'messages' => $this->messages,
            'business_context' => $this->businessContext,
        ];
    }
}
