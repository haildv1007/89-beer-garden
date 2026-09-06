<?php

namespace App\Support;

final class PhoneNumber
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === null || $digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0084')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '84') && strlen($digits) >= 10) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /** @return list<string> */
    public static function variants(?string $phone): array
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return [];
        }
        $variants = [$normalized];
        if (str_starts_with($normalized, '0')) {
            $international = '84'.substr($normalized, 1);
            $variants[] = $international;
            $variants[] = '+'.$international;
        }

        return array_values(array_unique($variants));
    }
}
