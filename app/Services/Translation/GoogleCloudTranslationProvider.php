<?php

namespace App\Services\Translation;

use App\Contracts\GoogleTranslationClient;
use App\Contracts\TranslationProvider;
use Throwable;

class GoogleCloudTranslationProvider implements TranslationProvider
{
    private const TARGETS = ['en' => 'en', 'zh' => 'zh-CN'];

    public function __construct(private GoogleTranslationClient $client, private TranslationCallBudget $budget) {}

    public function translate(string $sourceText, string $sourceLocale, string $targetLocale): TranslationProviderResult
    {
        if ($sourceLocale !== 'vi' || ! isset(self::TARGETS[$targetLocale]) || trim($sourceText) === ''
            || $sourceText !== strip_tags($sourceText) || ! $this->budget->consume()) {
            return TranslationProviderResult::unavailable();
        }
        try {
            $text = trim((string) $this->client->translate($sourceText, 'vi', self::TARGETS[$targetLocale]));

            return $text !== '' && mb_strlen($text) <= 10000
                ? TranslationProviderResult::success($text)
                : TranslationProviderResult::unavailable();
        } catch (Throwable) {
            return TranslationProviderResult::unavailable();
        }
    }
}
