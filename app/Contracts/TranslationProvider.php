<?php

namespace App\Contracts;

use App\Services\Translation\TranslationProviderResult;

interface TranslationProvider
{
    public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult;
}
