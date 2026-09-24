<?php

namespace App\Services\Recommendation;

use App\Contracts\RecommendationProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function __construct(
        private readonly RecommendationContextBuilder $contextBuilder,
        private readonly RuleBasedRecommendationEngine $engine,
        private readonly RecommendationResultHydrator $hydrator,
        private readonly RecommendationProvider $provider,
        private readonly ProviderOutputNormalizer $normalizer,
    ) {}

    /** @param list<array{product_id:int, quantity:int}> $cartItems */
    public function recommend(
        RecommendationInput $input,
        ?User $user = null,
        array $cartItems = [],
    ): RecommendationResult {
        $context = $this->contextBuilder->build($user, $cartItems);

        if (config('recommendation.ai_enabled')) {
            $providerInput = $this->contextBuilder->providerInput($context, $input, app()->getLocale());
            $providerResult = $this->provider->recommend($providerInput);
            $normalized = $this->normalizer->normalize($providerResult, $providerInput);

            if ($normalized->draft !== null) {
                $result = $this->hydrator->hydrate($normalized->draft, $input);
                if ($result->items !== [] && count($result->items) === count($normalized->draft->items)) {
                    return $result;
                }

                $this->logFallback('product_revalidation_failed', $providerResult);
            } else {
                $this->logFallback($normalized->failureCategory ?? 'invalid_output', $providerResult);
            }
        }

        $draft = $this->engine->recommend($context, $input);

        return $this->hydrator->hydrate($draft, $input);
    }

    private function logFallback(string $category, RecommendationProviderResult $providerResult): void
    {
        Log::warning('Recommendation provider fallback.', [
            'provider' => config('recommendation.provider'),
            'category' => $category,
            'http_status' => $providerResult->httpStatus,
            'duration_ms' => $providerResult->durationMs,
        ]);
    }
}
