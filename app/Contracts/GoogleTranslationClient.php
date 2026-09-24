<?php

namespace App\Contracts;

interface GoogleTranslationClient
{
    public function translate(string $text, string $sourceLocale, string $targetLocale): ?string;
}
