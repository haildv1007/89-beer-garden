<?php

namespace App\Services\SystemSetting;

final class SystemSettingCatalog
{
    public const NO_SHOW_TIMEOUT = 'no_show_timeout_minutes';

    public const CUSTOMER_ORDERING = 'customer_ordering_enabled';

    /** @return array<string, array{type: string, label: string, description: string, secret: bool}> */
    public function definitions(): array
    {
        return [
            self::NO_SHOW_TIMEOUT => [
                'type' => 'integer',
                'label' => 'setting.items.no_show_timeout_minutes.label',
                'description' => 'setting.items.no_show_timeout_minutes.description',
                'secret' => false,
            ],
            self::CUSTOMER_ORDERING => [
                'type' => 'boolean',
                'label' => 'setting.items.customer_ordering_enabled.label',
                'description' => 'setting.items.customer_ordering_enabled.description',
                'secret' => false,
            ],
        ];
    }

    /** @return array{type: string, label: string, description: string, secret: bool}|null */
    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    public function canonicalValue(string $key, mixed $value): ?string
    {
        if (! is_string($value) || $value !== trim($value)) {
            return null;
        }

        return match ($key) {
            self::NO_SHOW_TIMEOUT => preg_match('/^[1-9][0-9]*$/D', $value) === 1
                && strlen($value) <= 4 && (int) $value <= 1440 ? $value : null,
            self::CUSTOMER_ORDERING => in_array($value, ['true', 'false'], true) ? $value : null,
            default => null,
        };
    }

    public function validPersisted(string $key, ?string $type, mixed $value): bool
    {
        $definition = $this->definition($key);

        return $definition !== null && $type === $definition['type']
            && $this->canonicalValue($key, $value) !== null;
    }
}
