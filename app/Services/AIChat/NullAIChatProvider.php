<?php

namespace App\Services\AIChat;

use App\Contracts\AIChatProvider;

class NullAIChatProvider implements AIChatProvider
{
    public function classify(AIChatProviderInput $input): AIChatProviderResult
    {
        return AIChatProviderResult::unavailable('provider_unavailable');
    }
}
