<?php

namespace Tests\Unit;

use App\Contracts\GoogleTranslationClient;
use App\Contracts\TranslationProvider;
use App\Services\Translation\GoogleCloudTranslationProvider;
use App\Services\Translation\NullTranslationProvider;
use App\Services\Translation\TranslationCallBudget;
use RuntimeException;
use Tests\TestCase;

class GoogleCloudTranslationProviderTest extends TestCase
{
    public function test_binding_selects_google_only_with_complete_valid_configuration(): void
    {
        config()->set('translation.provider', 'null');
        $this->assertInstanceOf(NullTranslationProvider::class, app(TranslationProvider::class));
        config()->set('translation.provider', 'unknown');
        $this->assertInstanceOf(NullTranslationProvider::class, app(TranslationProvider::class));
        config()->set('translation.provider', 'google');
        config()->set('translation.google.project_id', 'project');
        config()->set('translation.google.credentials', 'missing.json');
        $this->assertInstanceOf(NullTranslationProvider::class, app(TranslationProvider::class));

        $path = tempnam(sys_get_temp_dir(), 'google-credentials-');
        config()->set('translation.google.credentials', $path);
        $this->assertInstanceOf(GoogleCloudTranslationProvider::class, app(TranslationProvider::class));
        unlink($path);
    }

    public function test_locale_mapping_and_budget_are_strict(): void
    {
        config()->set('translation.google.max_calls_per_request', 2);
        $client = new class implements GoogleTranslationClient
        {
            public array $calls = [];

            public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
            {
                $this->calls[] = [$sourceLocale, $targetLocale];

                return 'translated';
            }
        };
        $provider = new GoogleCloudTranslationProvider($client, new TranslationCallBudget);
        $this->assertTrue($provider->translate('Bia', 'vi', 'en')->successful);
        $this->assertTrue($provider->translate('Bia', 'vi', 'zh')->successful);
        $this->assertFalse($provider->translate('Bia', 'vi', 'en')->successful);
        $this->assertSame([['vi', 'en'], ['vi', 'zh-CN']], $client->calls);
    }

    public function test_failures_blank_oversized_html_and_invalid_locale_fail_closed(): void
    {
        config()->set('translation.google.max_calls_per_request', 10);
        foreach ([null, '', str_repeat('x', 10001), new RuntimeException('secret failure')] as $response) {
            $client = new class($response) implements GoogleTranslationClient
            {
                public function __construct(private mixed $response) {}

                public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
                {
                    if ($this->response instanceof \Throwable) {
                        throw $this->response;
                    }

                    return $this->response;
                }
            };
            $provider = new GoogleCloudTranslationProvider($client, new TranslationCallBudget);
            $this->assertFalse($provider->translate('Bia', 'vi', 'en')->successful);
        }
        $client = new class implements GoogleTranslationClient
        {
            public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
            {
                return 'x';
            }
        };
        $provider = new GoogleCloudTranslationProvider($client, new TranslationCallBudget);
        $this->assertFalse($provider->translate('<b>Bia</b>', 'vi', 'en')->successful);
        $this->assertFalse($provider->translate('Bia', 'vi', 'fr')->successful);
        $this->assertFalse($provider->translate('Bia', 'en', 'zh')->successful);
    }
}
