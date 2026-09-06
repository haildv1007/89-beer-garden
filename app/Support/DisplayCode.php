<?php

namespace App\Support;

final class DisplayCode
{
    public static function short(?string $code, int $suffixLength = 8): string
    {
        if (! $code || mb_strlen($code) <= 14) {
            return $code ?: '—';
        }

        $separator = str_contains($code, '-') ? '-' : '';
        $prefix = $separator ? mb_strstr($code, '-', true) : '';
        $suffix = mb_substr(str_replace('-', '', $code), -$suffixLength);

        return ($prefix ? $prefix.$separator : '').$suffix;
    }
}
