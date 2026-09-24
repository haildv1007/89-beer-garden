<?php

namespace App\Services\SystemSetting;

final class SystemSettingCatalog
{
    public const SITE_NAME = 'site_name';

    public const SITE_AUTHOR = 'site_author';

    public const SEO_TITLE = 'seo_title';

    public const SEO_DESCRIPTION = 'seo_description';

    public const SEO_KEYWORDS = 'seo_keywords';

    public const SITE_LOGO = 'site_logo_path';

    public const SITE_BANNER = 'site_banner_path';

    public const SITE_BANNER_VIDEO = 'site_banner_video_path';

    public const SITE_FAVICON = 'site_favicon_path';

    public const OG_TITLE = 'og_title';

    public const OG_DESCRIPTION = 'og_description';

    public const OG_IMAGE = 'og_image_path';

    public const CUSTOM_SCRIPT_HEAD = 'custom_script_head';

    public const CUSTOM_SCRIPT_BODY = 'custom_script_body';

    public const CUSTOM_SCRIPT_FOOTER = 'custom_script_footer';

    public const CONTACT_ADDRESS = 'contact_address';

    public const CONTACT_PHONE = 'contact_phone';

    public const CONTACT_EMAIL = 'contact_email';

    public const CONTACT_FACEBOOK_URL = 'contact_facebook_url';

    public const CONTACT_ZALO_URL = 'contact_zalo_url';

    public const OPENING_HOURS = 'opening_hours';

    public const MAP_URL = 'map_url';

    public const NO_SHOW_TIMEOUT = 'no_show_timeout_minutes';

    public const LATE_CHECK_IN_MINUTES = 'late_check_in_minutes';

    public const DELIVERY_FEE = 'delivery_fee';

    public const LOGIN_IDENTITY_ATTEMPTS = 'login_identity_attempts';

    public const LOGIN_IP_ATTEMPTS = 'login_ip_attempts';

    public const LOGIN_LOCK_MINUTES = 'login_lock_minutes';

    public const VIETQR_BANK_ID = 'vietqr_bank_id';

    public const VIETQR_ACCOUNT_NUMBER = 'vietqr_account_number';

    public const VIETQR_ACCOUNT_NAME = 'vietqr_account_name';

    public const VIETQR_TRANSFER_PREFIX = 'vietqr_transfer_prefix';

    public const SEPAY_WEBHOOK_API_KEY = 'sepay_webhook_api_key';

    public const GOOGLE_OAUTH_CLIENT_ID = 'google_oauth_client_id';

    public const GOOGLE_OAUTH_CLIENT_SECRET = 'google_oauth_client_secret';

    public const GOOGLE_TRANSLATION_PROJECT_ID = 'google_translation_project_id';

    public const GOOGLE_TRANSLATION_CREDENTIALS = 'google_translation_credentials_json';

    public const GEMINI_ENABLED = 'gemini_enabled';

    public const GEMINI_API_KEY = 'gemini_api_key';

    public const GEMINI_MODEL = 'gemini_model';

    public const GEMINI_MODELS = [
        'gemini-3.8-flash' => 'Gemini 3.8 Flash',
        'gemini-3.7-flash' => 'Gemini 3.7 Flash',
        'gemini-3.6-flash' => 'Gemini 3.6 Flash',
        'gemini-3.5-flash' => 'Gemini 3.5 Flash',
        'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash-Lite',
        'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash-Lite',
        'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview',
        'gemini-2.5-flash' => 'Gemini 2.5 Flash',
        'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite',
        'gemini-2.5-pro' => 'Gemini 2.5 Pro',
    ];

    public const DEFAULT_DELIVERY_FEE = 30000;

    public const MAX_DELIVERY_FEE = 10000000;

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return [
            self::SITE_NAME => $this->textDefinition('Tên website', 'Tên thương hiệu hiển thị ở header, footer và dữ liệu SEO.', 100),
            self::SITE_AUTHOR => $this->textDefinition('Tác giả / đơn vị sở hữu', 'Tên nhà hàng hoặc đơn vị sở hữu nội dung website.', 100),
            self::SEO_TITLE => $this->textDefinition('Tiêu đề SEO mặc định', 'Tiêu đề mặc định của trang chủ và khi trang không khai báo tiêu đề riêng.', 70),
            self::SEO_DESCRIPTION => $this->textareaDefinition('Mô tả website', 'Mô tả mặc định dành cho công cụ tìm kiếm, tối đa 320 ký tự.', 320),
            self::SEO_KEYWORDS => $this->textareaDefinition('Từ khóa SEO', 'Các từ khóa chính, phân cách bằng dấu phẩy.', 500),
            self::SITE_LOGO => $this->imageDefinition('Logo website', 'Logo dùng tại header và footer.', ['png', 'jpg', 'jpeg', 'webp']),
            self::SITE_BANNER => $this->imageDefinition('Ảnh banner trang chủ', 'Ảnh nền chính ở khu vực mở đầu trang chủ.', ['png', 'jpg', 'jpeg', 'webp']),
            self::SITE_BANNER_VIDEO => $this->mediaDefinition(
                'Video banner trang chủ',
                'Video chuyển động tùy chọn. Ảnh banner phía trên được dùng làm poster trong lúc video tải hoặc khi thiết bị tiết kiệm dữ liệu.',
                ['mp4', 'webm'],
                25600,
            ),
            self::SITE_FAVICON => $this->imageDefinition('Favicon', 'Biểu tượng hiển thị trên tab trình duyệt (nên dùng ảnh vuông).', ['png', 'jpg', 'jpeg', 'webp']),
            self::OG_TITLE => $this->textDefinition('Open Graph title', 'Tiêu đề mặc định khi chia sẻ website lên mạng xã hội.', 100),
            self::OG_DESCRIPTION => $this->textareaDefinition('Open Graph description', 'Mô tả mặc định khi chia sẻ website.', 320),
            self::OG_IMAGE => $this->imageDefinition('Open Graph image', 'Ảnh đại diện mặc định khi chia sẻ liên kết.', ['png', 'jpg', 'jpeg', 'webp']),
            self::CUSTOM_SCRIPT_HEAD => $this->textareaDefinition('Mã trong thẻ <head>', 'Google Analytics, Meta Pixel hoặc mã xác minh.', 12000),
            self::CUSTOM_SCRIPT_BODY => $this->textareaDefinition('Mã ngay sau thẻ <body>', 'Google Tag Manager noscript hoặc widget cần tải sớm.', 12000),
            self::CUSTOM_SCRIPT_FOOTER => $this->textareaDefinition('Mã trước thẻ </body>', 'Chat widget hoặc script cần tải cuối trang.', 12000),
            self::CONTACT_ADDRESS => $this->textareaDefinition(
                'setting.items.contact_address.label',
                'setting.items.contact_address.description',
                500,
            ),
            self::CONTACT_PHONE => $this->textDefinition(
                'setting.items.contact_phone.label',
                'setting.items.contact_phone.description',
                30,
            ),
            self::CONTACT_EMAIL => $this->textDefinition(
                'setting.items.contact_email.label',
                'setting.items.contact_email.description',
                254,
            ),
            self::CONTACT_FACEBOOK_URL => $this->textDefinition(
                'setting.items.contact_facebook_url.label',
                'setting.items.contact_facebook_url.description',
                2048,
            ),
            self::CONTACT_ZALO_URL => $this->textDefinition(
                'setting.items.contact_zalo_url.label',
                'setting.items.contact_zalo_url.description',
                2048,
            ),
            self::OPENING_HOURS => $this->textareaDefinition(
                'setting.items.opening_hours.label',
                'setting.items.opening_hours.description',
                1000,
            ),
            self::MAP_URL => $this->textDefinition(
                'setting.items.map_url.label',
                'setting.items.map_url.description',
                2048,
            ),
            self::NO_SHOW_TIMEOUT => [
                'type' => 'integer',
                'label' => 'setting.items.no_show_timeout_minutes.label',
                'description' => 'setting.items.no_show_timeout_minutes.description',
                'secret' => false,
            ],
            self::LATE_CHECK_IN_MINUTES => $this->integerDefinition(
                'Chuyển sang “Trễ check-in” sau (phút)',
                'Đặt bàn đã xác nhận nhưng chưa check-in sẽ tự chuyển sang trạng thái “Trễ check-in” sau số phút này tính từ giờ khách hẹn đến.',
                1,
                180,
                5,
            ),
            self::DELIVERY_FEE => [
                'type' => 'integer',
                'label' => 'setting.items.delivery_fee.label',
                'description' => 'setting.items.delivery_fee.description',
                'secret' => false,
            ],
            self::LOGIN_IDENTITY_ATTEMPTS => $this->integerDefinition(
                'Tạm khóa tài khoản sau số lần đăng nhập sai',
                'Ví dụ nhập 5: tài khoản sẽ bị tạm khóa sau 5 lần nhập sai mật khẩu trong khoảng thời gian theo dõi.',
                3,
                20,
                5,
            ),
            self::LOGIN_IP_ATTEMPTS => $this->integerDefinition(
                'Tạm khóa đăng nhập từ IP sau số lần sai',
                'IP là địa chỉ mạng của thiết bị. Ví dụ nhập 20: hệ thống tạm chặn đăng nhập từ IP đó sau tổng cộng 20 lần sai; không phải danh sách IP cho phép.',
                10,
                100,
                20,
            ),
            self::LOGIN_LOCK_MINUTES => $this->integerDefinition(
                'Thời gian tạm khóa đăng nhập (phút)',
                'Số phút hệ thống theo dõi các lần sai và tạm khóa khi vượt một trong hai ngưỡng trên.',
                1,
                60,
                5,
            ),
            self::VIETQR_BANK_ID => [
                'type' => 'string',
                'label' => 'setting.items.vietqr_bank_id.label',
                'description' => 'setting.items.vietqr_bank_id.description',
                'secret' => false,
            ],
            self::VIETQR_ACCOUNT_NUMBER => [
                'type' => 'string',
                'label' => 'setting.items.vietqr_account_number.label',
                'description' => 'setting.items.vietqr_account_number.description',
                'secret' => false,
            ],
            self::VIETQR_ACCOUNT_NAME => [
                'type' => 'string',
                'label' => 'setting.items.vietqr_account_name.label',
                'description' => 'setting.items.vietqr_account_name.description',
                'secret' => false,
            ],
            self::VIETQR_TRANSFER_PREFIX => [
                'type' => 'string',
                'label' => 'setting.items.vietqr_transfer_prefix.label',
                'description' => 'setting.items.vietqr_transfer_prefix.description',
                'secret' => false,
            ],
            self::SEPAY_WEBHOOK_API_KEY => [
                'type' => 'string',
                'label' => 'SePay Webhook API Key',
                'description' => 'Khóa bí mật SePay gửi trong header Authorization để xác thực webhook.',
                'secret' => true,
            ],
            self::GOOGLE_OAUTH_CLIENT_ID => [
                'type' => 'string',
                'label' => 'Google OAuth Client ID',
                'description' => 'Client ID của ứng dụng Web trong Google Cloud Console.',
                'secret' => false,
            ],
            self::GOOGLE_OAUTH_CLIENT_SECRET => [
                'type' => 'string',
                'label' => 'Google OAuth Client Secret',
                'description' => 'Khóa bí mật dùng cho đăng nhập Google; giá trị được mã hóa và không hiển thị lại.',
                'secret' => true,
            ],
            self::GOOGLE_TRANSLATION_PROJECT_ID => [
                'type' => 'string',
                'label' => 'Google Translation Project ID',
                'description' => 'Project ID đã bật Cloud Translation API.',
                'secret' => false,
            ],
            self::GOOGLE_TRANSLATION_CREDENTIALS => [
                'type' => 'string',
                'label' => 'Google Translation Service Account',
                'description' => 'Nội dung JSON của service account có quyền Cloud Translation; được mã hóa khi lưu.',
                'secret' => true,
            ],
            self::GEMINI_ENABLED => [
                'type' => 'boolean',
                'label' => 'Bật trợ lý AI Gemini',
                'description' => 'Hiển thị khung chat AI và dùng Gemini để hiểu yêu cầu của khách. Khi tắt, khung chat sẽ được ẩn.',
                'secret' => false,
                'input' => 'toggle',
            ],
            self::GEMINI_API_KEY => [
                'type' => 'string',
                'label' => 'Gemini API Key',
                'description' => 'API key tạo trong Google AI Studio; được mã hóa và không hiển thị lại.',
                'secret' => true,
                'max' => 500,
            ],
            self::GEMINI_MODEL => [
                'type' => 'string',
                'label' => 'Model Gemini',
                'description' => 'Flash phù hợp cho chat nhanh; Flash-Lite tiết kiệm hơn; Pro dành cho yêu cầu phức tạp.',
                'secret' => false,
                'input' => 'select',
                'options' => self::GEMINI_MODELS,
                'default' => 'gemini-2.5-flash',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
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
            self::SITE_NAME, self::SITE_AUTHOR => $this->boundedText($value, 2, 100),
            self::CONTACT_ADDRESS => $this->boundedText($value, 5, 500),
            self::CONTACT_PHONE => $this->validPhone($value) ? $value : null,
            self::CONTACT_EMAIL => filter_var($value, FILTER_VALIDATE_EMAIL) !== false && strlen($value) <= 254
                ? mb_strtolower($value)
                : null,
            self::CONTACT_FACEBOOK_URL => $this->canonicalSocialUrl($value, 'facebook'),
            self::CONTACT_ZALO_URL => $this->canonicalSocialUrl($value, 'zalo'),
            self::OPENING_HOURS => $this->boundedText($value, 2, 1000),
            self::MAP_URL => $this->validHttpsUrl($value, 2048) ? $value : null,
            self::SEO_TITLE => $this->boundedText($value, 5, 70),
            self::SEO_DESCRIPTION, self::OG_DESCRIPTION => $this->boundedText($value, 10, 320),
            self::SEO_KEYWORDS => $this->boundedText($value, 2, 500),
            self::OG_TITLE => $this->boundedText($value, 2, 100),
            self::CUSTOM_SCRIPT_HEAD, self::CUSTOM_SCRIPT_BODY, self::CUSTOM_SCRIPT_FOOTER => $this->boundedText($value, 1, 12000),
            self::SITE_LOGO, self::SITE_BANNER, self::SITE_FAVICON, self::OG_IMAGE => preg_match('/^system-settings\/[A-Za-z0-9._\/-]+\.(?:png|jpe?g|webp)$/iD', $value) === 1
                    ? $value
                    : null,
            self::SITE_BANNER_VIDEO => preg_match('/^system-settings\/[A-Za-z0-9._\/-]+\.(?:mp4|webm)$/iD', $value) === 1
                    ? $value
                    : null,
            self::NO_SHOW_TIMEOUT => preg_match('/^[1-9][0-9]*$/D', $value) === 1 &&
            strlen($value) <= 4 &&
            (int) $value <= 1440
                ? $value
                : null,
            self::LATE_CHECK_IN_MINUTES => $this->boundedInteger($value, 1, 180),
            self::LOGIN_IDENTITY_ATTEMPTS => $this->boundedInteger($value, 3, 20),
            self::LOGIN_IP_ATTEMPTS => $this->boundedInteger($value, 10, 100),
            self::LOGIN_LOCK_MINUTES => $this->boundedInteger($value, 1, 60),
            self::DELIVERY_FEE => preg_match('/^(0|[1-9][0-9]*)$/D', $value) === 1 &&
            strlen($value) <= 8 &&
            (int) $value <= self::MAX_DELIVERY_FEE
                ? $value
                : null,
            self::VIETQR_BANK_ID => preg_match('/^[A-Za-z0-9]{2,20}$/D', $value) === 1 ? strtoupper($value) : null,
            self::VIETQR_ACCOUNT_NUMBER => preg_match('/^[A-Za-z0-9]{6,19}$/D', $value) === 1
                ? strtoupper($value)
                : null,
            self::VIETQR_ACCOUNT_NAME => preg_match('/^[\pL\pN .-]{3,100}$/uD', $value) === 1
                ? mb_strtoupper($value)
                : null,
            self::VIETQR_TRANSFER_PREFIX => preg_match('/^[A-Za-z0-9]{1,8}$/D', $value) === 1
                ? strtoupper($value)
                : null,
            self::SEPAY_WEBHOOK_API_KEY => strlen($value) >= 16 && strlen($value) <= 255 ? $value : null,
            self::GOOGLE_OAUTH_CLIENT_ID => strlen($value) <= 255 &&
            preg_match('/^[A-Za-z0-9._-]+\.apps\.googleusercontent\.com$/D', $value) === 1
                ? $value
                : null,
            self::GOOGLE_OAUTH_CLIENT_SECRET => strlen($value) >= 8 && strlen($value) <= 500 ? $value : null,
            self::GOOGLE_TRANSLATION_PROJECT_ID => preg_match('/^[a-z][a-z0-9-]{4,62}$/D', $value) === 1
                ? $value
                : null,
            self::GOOGLE_TRANSLATION_CREDENTIALS => $this->validServiceAccountJson($value) ? $value : null,
            self::GEMINI_ENABLED => in_array($value, ['0', '1'], true) ? $value : null,
            self::GEMINI_API_KEY => strlen($value) >= 20 && strlen($value) <= 500 ? $value : null,
            self::GEMINI_MODEL => array_key_exists($value, self::GEMINI_MODELS) ? $value : null,
            default => null,
        };
    }

    private function validServiceAccountJson(string $value): bool
    {
        if (strlen($value) > 12000) {
            return false;
        }
        $json = json_decode($value, true);

        return is_array($json) &&
            ($json['type'] ?? null) === 'service_account' &&
            is_string($json['client_email'] ?? null) &&
            is_string($json['private_key'] ?? null);
    }

    public function validPersisted(string $key, ?string $type, mixed $value): bool
    {
        $definition = $this->definition($key);

        return $definition !== null && $type === $definition['type'] && $this->canonicalValue($key, $value) !== null;
    }

    /** @return array<string, array<string, mixed>> */
    public function groups(): array
    {
        return [
            'seo' => [
                'label' => 'SEO & Thông tin',
                'description' => 'Thông tin cơ bản hiển thị trên website và công cụ tìm kiếm.',
                'keys' => [self::SITE_NAME, self::SITE_AUTHOR, self::SEO_TITLE, self::SEO_DESCRIPTION, self::SEO_KEYWORDS],
            ],
            'social' => [
                'label' => 'SEO mạng xã hội',
                'description' => 'Nội dung mặc định khi chia sẻ liên kết.',
                'keys' => [self::OG_TITLE, self::OG_DESCRIPTION, self::OG_IMAGE],
            ],
            'images' => [
                'label' => 'Logo & Hình ảnh',
                'description' => 'Logo, ảnh hoặc video banner trang chủ và favicon.',
                'keys' => [self::SITE_LOGO, self::SITE_BANNER, self::SITE_BANNER_VIDEO, self::SITE_FAVICON],
            ],
            'contact' => [
                'label' => 'Liên hệ & cửa hàng',
                'description' => 'Thông tin công khai dùng trên trang Liên hệ và bởi trợ lý chăm sóc khách hàng.',
                'keys' => [
                    self::CONTACT_ADDRESS,
                    self::CONTACT_PHONE,
                    self::CONTACT_EMAIL,
                    self::CONTACT_FACEBOOK_URL,
                    self::CONTACT_ZALO_URL,
                    self::OPENING_HOURS,
                    self::MAP_URL,
                ],
            ],
            'operation' => [
                'label' => 'Vận hành',
                'description' => 'Thiết lập quy trình bán hàng và phục vụ.',
                'keys' => [
                    self::NO_SHOW_TIMEOUT,
                    self::LATE_CHECK_IN_MINUTES,
                    self::DELIVERY_FEE,
                    self::LOGIN_IDENTITY_ATTEMPTS,
                    self::LOGIN_IP_ATTEMPTS,
                    self::LOGIN_LOCK_MINUTES,
                ],
            ],
            'payment' => [
                'label' => 'Thanh toán VietQR',
                'description' => 'Thông tin tài khoản nhận chuyển khoản của nhà hàng.',
                'keys' => [self::VIETQR_BANK_ID, self::VIETQR_ACCOUNT_NUMBER, self::VIETQR_ACCOUNT_NAME, self::VIETQR_TRANSFER_PREFIX, self::SEPAY_WEBHOOK_API_KEY],
            ],
            'connections' => [
                'label' => 'Kết nối',
                'description' => 'Đăng nhập Google, dịch tự động và trợ lý Gemini.',
                'keys' => [
                    self::GOOGLE_OAUTH_CLIENT_ID,
                    self::GOOGLE_OAUTH_CLIENT_SECRET,
                    self::GOOGLE_TRANSLATION_PROJECT_ID,
                    self::GOOGLE_TRANSLATION_CREDENTIALS,
                    self::GEMINI_ENABLED,
                    self::GEMINI_API_KEY,
                    self::GEMINI_MODEL,
                ],
            ],
            'scripts' => [
                'label' => 'Mã tùy chỉnh',
                'description' => 'Chèn mã theo dõi hoặc widget vào website.',
                'keys' => [self::CUSTOM_SCRIPT_HEAD, self::CUSTOM_SCRIPT_BODY, self::CUSTOM_SCRIPT_FOOTER],
            ],
        ];
    }

    /** @param array<int, string> $extensions */
    private function imageDefinition(string $label, string $description, array $extensions): array
    {
        return compact('label', 'description') + [
            'type' => 'string',
            'secret' => false,
            'input' => 'image',
            'extensions' => $extensions,
        ];
    }

    /** @param array<int, string> $extensions */
    private function mediaDefinition(string $label, string $description, array $extensions, int $maxKilobytes): array
    {
        return compact('label', 'description', 'extensions', 'maxKilobytes') + [
            'type' => 'string',
            'secret' => false,
            'input' => 'media',
        ];
    }

    /** @return array<string, mixed> */
    private function integerDefinition(string $label, string $description, int $min, int $max, int $default): array
    {
        return compact('label', 'description', 'min', 'max', 'default') + [
            'type' => 'integer',
            'secret' => false,
        ];
    }

    private function boundedInteger(string $value, int $min, int $max): ?string
    {
        return preg_match('/^(0|[1-9][0-9]*)$/D', $value) === 1 && (int) $value >= $min && (int) $value <= $max
            ? $value
            : null;
    }

    private function textDefinition(string $label, string $description, int $max): array
    {
        return compact('label', 'description', 'max') + ['type' => 'string', 'secret' => false, 'input' => 'text'];
    }

    private function textareaDefinition(string $label, string $description, int $max): array
    {
        return compact('label', 'description', 'max') + ['type' => 'string', 'secret' => false, 'input' => 'textarea'];
    }

    private function boundedText(string $value, int $min, int $max): ?string
    {
        $length = mb_strlen($value);

        return $length >= $min && $length <= $max && ! str_contains($value, "\0") ? $value : null;
    }

    private function validPhone(string $value): bool
    {
        return strlen($value) <= 30 && preg_match('/^\+?[0-9][0-9 .()-]{7,28}$/D', $value) === 1;
    }

    private function validHttpsUrl(string $value, int $max): bool
    {
        if (strlen($value) > $max || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return parse_url($value, PHP_URL_SCHEME) === 'https' && is_string(parse_url($value, PHP_URL_HOST));
    }

    /** @param list<string> $allowedHosts */
    private function validServiceUrl(string $value, array $allowedHosts): bool
    {
        if (! $this->validHttpsUrl($value, 2048)) {
            return false;
        }

        return in_array(mb_strtolower((string) parse_url($value, PHP_URL_HOST)), $allowedHosts, true);
    }

    private function canonicalSocialUrl(string $value, string $service): ?string
    {
        $hosts = $service === 'facebook'
            ? ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'm.me']
            : ['zalo.me', 'www.zalo.me', 'chat.zalo.me'];
        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $value) === 1) {
            $url = $value;
        } elseif (preg_match('/^(?:'.implode('|', array_map(fn (string $host): string => preg_quote($host, '/'), $hosts)).')(?:\/|$)/i', $value) === 1) {
            $url = 'https://'.$value;
        } elseif (preg_match('/^@?[A-Za-z0-9._-]{3,80}$/D', $value) === 1) {
            $identifier = ltrim($value, '@');
            $url = 'https://'.($service === 'facebook' ? 'facebook.com/' : 'zalo.me/').$identifier;
        } else {
            return null;
        }

        return $this->validServiceUrl($url, $hosts) ? $url : null;
    }
}
