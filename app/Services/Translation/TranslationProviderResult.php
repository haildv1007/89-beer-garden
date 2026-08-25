<?php

namespace App\Services\Translation;

final readonly class TranslationProviderResult
{
    private function __construct(public bool $successful, public ?string $text) {}

    public static function success(string $text): self
    {
        return new self(true, $text);
    }

    public static function unavailable(): self
    {
        return new self(false, null);
    }
}
