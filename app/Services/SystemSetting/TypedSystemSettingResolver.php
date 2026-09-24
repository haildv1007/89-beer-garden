<?php

namespace App\Services\SystemSetting;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class TypedSystemSettingResolver
{
    /** @var array<string, string>|null */
    private ?array $resolvedPublicSiteSettings = null;

    public function __construct(private readonly SystemSettingCatalog $catalog) {}

    public function noShowTimeoutMinutes(bool $lockForUpdate = false): ?int
    {
        $value = $this->canonical(SystemSettingCatalog::NO_SHOW_TIMEOUT, $lockForUpdate);

        return $value === null ? null : (int) $value;
    }

    public function lateCheckInMinutes(): int
    {
        return (int) ($this->canonical(SystemSettingCatalog::LATE_CHECK_IN_MINUTES, false) ?? 5);
    }

    public function value(string $key): ?string
    {
        return $this->catalog->definition($key) === null ? null : $this->canonical($key, false);
    }

    public function deliveryFee(bool $lockForUpdate = false): ?int
    {
        $value = $this->canonical(SystemSettingCatalog::DELIVERY_FEE, $lockForUpdate);

        return $value === null ? null : (int) $value;
    }

    /** @return array{identity_attempts:int,ip_attempts:int,lock_minutes:int} */
    public function loginProtection(): array
    {
        return [
            'identity_attempts' => (int) ($this->canonical(SystemSettingCatalog::LOGIN_IDENTITY_ATTEMPTS, false) ?? 5),
            'ip_attempts' => (int) ($this->canonical(SystemSettingCatalog::LOGIN_IP_ATTEMPTS, false) ?? 20),
            'lock_minutes' => (int) ($this->canonical(SystemSettingCatalog::LOGIN_LOCK_MINUTES, false) ?? 5),
        ];
    }

    /** @return array{bank_id:string,account_number:string,account_name:string,prefix:string}|null */
    public function vietQr(): ?array
    {
        $values = [
            'bank_id' => $this->canonical(SystemSettingCatalog::VIETQR_BANK_ID, false),
            'account_number' => $this->canonical(SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER, false),
            'account_name' => $this->canonical(SystemSettingCatalog::VIETQR_ACCOUNT_NAME, false),
            'prefix' => $this->canonical(SystemSettingCatalog::VIETQR_TRANSFER_PREFIX, false),
        ];

        return in_array(null, $values, true) ? null : $values;
    }

    public function sePayWebhookApiKey(): ?string
    {
        return $this->canonical(SystemSettingCatalog::SEPAY_WEBHOOK_API_KEY, false);
    }

    /** @return array{address:?string,phone:?string,email:?string,facebook_url:?string,zalo_url:?string,opening_hours:?string,map_url:?string} */
    public function contactInformation(): array
    {
        return [
            'address' => $this->canonical(SystemSettingCatalog::CONTACT_ADDRESS, false),
            'phone' => $this->canonical(SystemSettingCatalog::CONTACT_PHONE, false),
            'email' => $this->canonical(SystemSettingCatalog::CONTACT_EMAIL, false),
            'facebook_url' => $this->canonical(SystemSettingCatalog::CONTACT_FACEBOOK_URL, false),
            'zalo_url' => $this->canonical(SystemSettingCatalog::CONTACT_ZALO_URL, false),
            'opening_hours' => $this->canonical(SystemSettingCatalog::OPENING_HOURS, false),
            'map_url' => $this->canonical(SystemSettingCatalog::MAP_URL, false),
        ];
    }

    /** @return array{client_id:string,client_secret:string}|null */
    public function googleOAuth(): ?array
    {
        $values = [
            'client_id' => $this->canonical(SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_ID, false),
            'client_secret' => $this->canonical(SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_SECRET, false),
        ];

        return in_array(null, $values, true) ? null : $values;
    }

    /** @return array{project_id:string,credentials:array<string,mixed>}|null */
    public function googleTranslation(): ?array
    {
        $project = $this->canonical(SystemSettingCatalog::GOOGLE_TRANSLATION_PROJECT_ID, false);
        $credentials = $this->canonical(SystemSettingCatalog::GOOGLE_TRANSLATION_CREDENTIALS, false);
        $decoded = is_string($credentials) ? json_decode($credentials, true) : null;

        return $project !== null && is_array($decoded) ? ['project_id' => $project, 'credentials' => $decoded] : null;
    }

    /** @return array{enabled:bool,api_key:?string,model:string} */
    public function gemini(): array
    {
        $enabled = $this->canonical(SystemSettingCatalog::GEMINI_ENABLED, false);
        $apiKey = $this->canonical(SystemSettingCatalog::GEMINI_API_KEY, false);
        $model = $this->canonical(SystemSettingCatalog::GEMINI_MODEL, false);

        return [
            'enabled' => $enabled === null
                ? ((bool) config('features.ai_chat') || (bool) config('recommendation.ai_enabled'))
                : $enabled === '1',
            'api_key' => $apiKey ?? (is_string(config('recommendation.gemini.api_key'))
                ? config('recommendation.gemini.api_key')
                : null),
            'model' => $model ?? (string) config('recommendation.gemini.model', 'gemini-2.5-flash'),
        ];
    }

    /** @return array<string, string> */
    public function publicSiteSettings(): array
    {
        if ($this->resolvedPublicSiteSettings !== null) {
            return $this->resolvedPublicSiteSettings;
        }

        $keys = [
            SystemSettingCatalog::SITE_NAME,
            SystemSettingCatalog::SITE_AUTHOR,
            SystemSettingCatalog::SEO_TITLE,
            SystemSettingCatalog::SEO_DESCRIPTION,
            SystemSettingCatalog::SEO_KEYWORDS,
            SystemSettingCatalog::SITE_LOGO,
            SystemSettingCatalog::SITE_BANNER,
            SystemSettingCatalog::SITE_BANNER_VIDEO,
            SystemSettingCatalog::SITE_FAVICON,
            SystemSettingCatalog::OG_TITLE,
            SystemSettingCatalog::OG_DESCRIPTION,
            SystemSettingCatalog::OG_IMAGE,
            SystemSettingCatalog::CUSTOM_SCRIPT_HEAD,
            SystemSettingCatalog::CUSTOM_SCRIPT_BODY,
            SystemSettingCatalog::CUSTOM_SCRIPT_FOOTER,
            SystemSettingCatalog::CONTACT_ADDRESS,
            SystemSettingCatalog::CONTACT_PHONE,
            SystemSettingCatalog::CONTACT_EMAIL,
            SystemSettingCatalog::CONTACT_FACEBOOK_URL,
            SystemSettingCatalog::CONTACT_ZALO_URL,
            SystemSettingCatalog::OPENING_HOURS,
            SystemSettingCatalog::MAP_URL,
        ];

        return $this->resolvedPublicSiteSettings = SystemSetting::query()
            ->whereIn('key', $keys)
            ->get(['key', 'value', 'type'])
            ->filter(fn (SystemSetting $setting): bool => $this->catalog->validPersisted(
                $setting->key,
                $setting->type,
                $setting->value,
            ))
            ->mapWithKeys(fn (SystemSetting $setting): array => [$setting->key => $setting->value])
            ->all();
    }

    private function canonical(string $key, bool $lockForUpdate): ?string
    {
        $setting = SystemSetting::query()
            ->where('key', $key)
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();

        $value = $setting?->value;
        if ($setting !== null && ($this->catalog->definition($key)['secret'] ?? false) && is_string($value)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (Throwable) {
                return null;
            }
        }
        if ($setting === null || ! $this->catalog->validPersisted($key, $setting->type, $value)) {
            return null;
        }

        return in_array($key, [SystemSettingCatalog::CONTACT_FACEBOOK_URL, SystemSettingCatalog::CONTACT_ZALO_URL], true)
            ? $this->catalog->canonicalValue($key, $value)
            : $value;
    }
}
