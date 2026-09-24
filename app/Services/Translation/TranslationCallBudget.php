<?php

namespace App\Services\Translation;

class TranslationCallBudget
{
    private int $used = 0;

    public function consume(): bool
    {
        $maximum = max(0, (int) config('translation.google.max_calls_per_request', 10));
        if ($this->used >= $maximum) {
            return false;
        }
        $this->used++;

        return true;
    }

    public function used(): int
    {
        return $this->used;
    }
}
