<?php

namespace App\Services\Recommendation;

use App\Contracts\RecommendationProvider;

class NullRecommendationProvider implements RecommendationProvider
{
    public function recommend(RecommendationProviderInput $input): RecommendationProviderResult
    {
        return RecommendationProviderResult::unavailable('provider_unavailable');
    }
}
