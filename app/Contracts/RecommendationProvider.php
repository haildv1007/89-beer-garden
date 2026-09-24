<?php

namespace App\Contracts;

use App\Services\Recommendation\RecommendationProviderInput;
use App\Services\Recommendation\RecommendationProviderResult;

interface RecommendationProvider
{
    public function recommend(RecommendationProviderInput $input): RecommendationProviderResult;
}
