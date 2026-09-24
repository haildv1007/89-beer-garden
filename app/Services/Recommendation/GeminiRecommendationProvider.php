<?php

namespace App\Services\Recommendation;

use App\Contracts\RecommendationProvider;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiRecommendationProvider implements RecommendationProvider
{
    public function __construct(private readonly TypedSystemSettingResolver $settings) {}

    public function recommend(RecommendationProviderInput $input): RecommendationProviderResult
    {
        $startedAt = hrtime(true);
        $gemini = $this->settings->gemini();
        $url = sprintf(
            '%s/v1beta/models/%s:generateContent',
            rtrim((string) config('recommendation.gemini.base_url'), '/'),
            rawurlencode($gemini['model']),
        );

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => (string) $gemini['api_key']])
                ->timeout((float) config('recommendation.gemini.timeout_seconds', 2.5))
                ->post($url, $this->requestBody($input));
        } catch (ConnectionException $exception) {
            $category = str_contains(strtolower($exception->getMessage()), 'timed out')
                ? 'timeout'
                : 'connection_error';

            return RecommendationProviderResult::unavailable($category, $this->durationMs($startedAt));
        }

        if (! $response->successful()) {
            return RecommendationProviderResult::unavailable(
                'http_error',
                $this->durationMs($startedAt),
                $response->status(),
            );
        }

        $content = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($content) || trim($content) === '') {
            return RecommendationProviderResult::unavailable('empty_response', $this->durationMs($startedAt));
        }

        return RecommendationProviderResult::success($content, $this->durationMs($startedAt));
    }

    /** @return array<string, mixed> */
    private function requestBody(RecommendationProviderInput $input): array
    {
        return [
            'systemInstruction' => [
                'parts' => [[
                    'text' => implode(' ', [
                        'Recommend a temporary meal set using only the supplied product_id values.',
                        'Return JSON matching the schema. Do not return prices, totals, HTML, or business actions.',
                        'Choose at most 6 unique products with quantity from 1 to 50.',
                        'Write reason and summary in the requested locale.',
                        'Preferences are not allergy, nutrition, or medical guarantees.',
                    ]),
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => json_encode($input->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'minItems' => 1,
                            'maxItems' => 6,
                            'items' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'product_id' => ['type' => 'integer'],
                                    'quantity' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                                    'reason' => ['type' => 'string'],
                                ],
                                'required' => ['product_id', 'quantity', 'reason'],
                            ],
                        ],
                        'summary' => ['type' => 'string'],
                    ],
                    'required' => ['items', 'summary'],
                ],
            ],
        ];
    }

    private function durationMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
