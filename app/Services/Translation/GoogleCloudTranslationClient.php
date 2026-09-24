<?php

namespace App\Services\Translation;

use App\Contracts\GoogleTranslationClient;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;

class GoogleCloudTranslationClient implements GoogleTranslationClient
{
    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
    {
        $admin = $this->settings->googleTranslation();
        $project = $admin['project_id'] ?? (string) config('translation.google.project_id');
        $credentials = $admin['credentials'] ?? (string) config('translation.google.credentials');
        $client = new TranslationServiceClient([
            'credentials' => $credentials,
            'transport' => 'rest',
        ]);
        try {
            $request = new TranslateTextRequest()
                ->setParent(TranslationServiceClient::locationName($project, 'global'))
                ->setContents([$text])
                ->setMimeType('text/plain')
                ->setSourceLanguageCode($sourceLocale)
                ->setTargetLanguageCode($targetLocale);
            $response = $client->translateText($request, [
                'timeoutMillis' => max(100, (int) ((float) config('translation.google.timeout_seconds', 3) * 1000)),
                'retrySettings' => ['retriesEnabled' => false],
            ]);

            return $response->getTranslations()[0]?->getTranslatedText();
        } finally {
            $client->close();
        }
    }
}
