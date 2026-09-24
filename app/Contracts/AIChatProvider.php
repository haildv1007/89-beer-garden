<?php

namespace App\Contracts;

use App\Services\AIChat\AIChatProviderInput;
use App\Services\AIChat\AIChatProviderResult;

interface AIChatProvider
{
    public function classify(AIChatProviderInput $input): AIChatProviderResult;
}
