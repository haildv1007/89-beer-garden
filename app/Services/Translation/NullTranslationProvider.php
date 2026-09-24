<?php

namespace App\Services\Translation;

use App\Contracts\TranslationProvider;

class NullTranslationProvider implements TranslationProvider
{
    public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
    {
        return TranslationProviderResult::unavailable();
    }
}
