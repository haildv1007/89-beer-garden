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

    public const SITE_FAVICON = 'site_favicon_path';

    public const OG_TITLE = 'og_title';

    public const OG_DESCRIPTION = 'og_description';

    public const OG_IMAGE = 'og_image_path';

    public const TWITTER_CARD = 'twitter_card';

    public const TWITTER_TITLE = 'twitter_title';

    public const TWITTER_DESCRIPTION = 'twitter_description';

    public const TWITTER_IMAGE = 'twitter_image_path';

    public const CUSTOM_SCRIPT_HEAD = 'custom_script_head';

    public const CUSTOM_SCRIPT_BODY = 'custom_script_body';

    public const CUSTOM_SCRIPT_FOOTER = 'custom_script_footer';

    public const NO_SHOW_TIMEOUT = 'no_show_timeout_minutes';

    public const CUSTOMER_ORDERING = 'customer_ordering_enabled';

    public const DELIVERY_FEE = 'delivery_fee';

    public const VIETQR_BANK_ID = 'vietqr_bank_id';

    public const VIETQR_ACCOUNT_NUMBER = 'vietqr_account_number';

    public const VIETQR_ACCOUNT_NAME = 'vietqr_account_name';

    public const VIETQR_TRANSFER_PREFIX = 'vietqr_transfer_prefix';

    public const GOOGLE_OAUTH_CLIENT_ID = 'google_oauth_client_id';

    public const GOOGLE_OAUTH_CLIENT_SECRET = 'google_oauth_client_secret';

    public const GOOGLE_TRANSLATION_PROJECT_ID = 'google_translation_project_id';

    public const GOOGLE_TRANSLATION_CREDENTIALS = 'google_translation_credentials_json';

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
            self::SITE_FAVICON => $this->imageDefinition('Favicon', 'Biểu tượng hiển thị trên tab trình duyệt (nên dùng ảnh vuông).', ['png', 'jpg', 'jpeg', 'webp']),
            self::OG_TITLE => $this->textDefinition('Open Graph title', 'Tiêu đề mặc định khi chia sẻ website lên mạng xã hội.', 100),
            self::OG_DESCRIPTION => $this->textareaDefinition('Open Graph description', 'Mô tả mặc định khi chia sẻ website.', 320),
            self::OG_IMAGE => $this->imageDefinition('Open Graph image', 'Ảnh đại diện mặc định khi chia sẻ liên kết.', ['png', 'jpg', 'jpeg', 'webp']),
            self::TWITTER_CARD => [
                'type' => 'string',
                'label' => 'Twitter card',
                'description' => 'Kiểu thẻ hiển thị khi chia sẻ lên X/Twitter.',
                'secret' => false,
                'input' => 'select',
                'options' => ['summary' => 'Summary', 'summary_large_image' => 'Summary large image'],
            ],
            self::TWITTER_TITLE => $this->textDefinition('Twitter title', 'Tiêu đề riêng cho X/Twitter; để trống cấu hình thì dùng Open Graph title.', 100),
            self::TWITTER_DESCRIPTION => $this->textareaDefinition('Twitter description', 'Mô tả riêng cho X/Twitter.', 320),
            self::TWITTER_IMAGE => $this->imageDefinition('Twitter image', 'Ảnh riêng cho X/Twitter.', ['png', 'jpg', 'jpeg', 'webp']),
            self::CUSTOM_SCRIPT_HEAD => $this->textareaDefinition('Mã trong thẻ <head>', 'Google Analytics, Meta Pixel hoặc mã xác minh.', 12000),
            self::CUSTOM_SCRIPT_BODY => $this->textareaDefinition('Mã ngay sau thẻ <body>', 'Google Tag Manager noscript hoặc widget cần tải sớm.', 12000),
            self::CUSTOM_SCRIPT_FOOTER => $this->textareaDefinition('Mã trước thẻ </body>', 'Chat widget hoặc script cần tải cuối trang.', 12000),
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
            self::DELIVERY_FEE => [
                'type' => 'integer',
                'label' => 'setting.items.delivery_fee.label',
                'description' => 'setting.items.delivery_fee.description',
                'secret' => false,
            ],
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
            self::SEO_TITLE => $this->boundedText($value, 5, 70),
            self::SEO_DESCRIPTION, self::OG_DESCRIPTION, self::TWITTER_DESCRIPTION => $this->boundedText($value, 10, 320),
            self::SEO_KEYWORDS => $this->boundedText($value, 2, 500),
            self::OG_TITLE, self::TWITTER_TITLE => $this->boundedText($value, 2, 100),
            self::CUSTOM_SCRIPT_HEAD, self::CUSTOM_SCRIPT_BODY, self::CUSTOM_SCRIPT_FOOTER => $this->boundedText($value, 1, 12000),
            self::SITE_LOGO, self::SITE_BANNER, self::SITE_FAVICON, self::OG_IMAGE, self::TWITTER_IMAGE => preg_match('/^system-settings\/[A-Za-z0-9._\/-]+\.(?:png|jpe?g|webp)$/iD', $value) === 1
                    ? $value
                    : null,
            self::TWITTER_CARD => in_array($value, ['summary', 'summary_large_image'], true) ? $value : null,
            self::NO_SHOW_TIMEOUT => preg_match('/^[1-9][0-9]*$/D', $value) === 1 &&
            strlen($value) <= 4 &&
            (int) $value <= 1440
                ? $value
                : null,
            self::CUSTOMER_ORDERING => in_array($value, ['true', 'false'], true) ? $value : null,
            self::DELIVERY_FEE => preg_match('/^(0|[1-9][0-9]*)$/D', $value) === 1 &&
            strlen($value) <= 8 &&
            (int) $value <= self::MAX_DELIVERY_FEE
                ? $value
                : null,
            self::VIETQR_BANK_ID => preg_match('/^[A-Za-z0-9]{2,20}$/D', $value) === 1 ? strtoupper($value) : null,
            self::VIETQR_ACCOUNT_NUMBER => preg_match('/^[0-9]{6,19}$/D', $value) === 1 ? $value : null,
            self::VIETQR_ACCOUNT_NAME => preg_match('/^[\pL\pN .-]{3,100}$/uD', $value) === 1
                ? mb_strtoupper($value)
                : null,
            self::VIETQR_TRANSFER_PREFIX => preg_match('/^[A-Za-z0-9]{1,8}$/D', $value) === 1
                ? strtoupper($value)
                : null,
            self::GOOGLE_OAUTH_CLIENT_ID => strlen($value) <= 255 &&
            preg_match('/^[A-Za-z0-9._-]+\.apps\.googleusercontent\.com$/D', $value) === 1
                ? $value
                : null,
            self::GOOGLE_OAUTH_CLIENT_SECRET => strlen($value) >= 8 && strlen($value) <= 500 ? $value : null,
            self::GOOGLE_TRANSLATION_PROJECT_ID => preg_match('/^[a-z][a-z0-9-]{4,62}$/D', $value) === 1
                ? $value
                : null,
            self::GOOGLE_TRANSLATION_CREDENTIALS => $this->validServiceAccountJson($value) ? $value : null,
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
                'keys' => [self::OG_TITLE, self::OG_DESCRIPTION, self::OG_IMAGE, self::TWITTER_CARD, self::TWITTER_TITLE, self::TWITTER_DESCRIPTION, self::TWITTER_IMAGE],
            ],
            'images' => [
                'label' => 'Logo & Hình ảnh',
                'description' => 'Logo, banner trang chủ và favicon.',
                'keys' => [self::SITE_LOGO, self::SITE_BANNER, self::SITE_FAVICON],
            ],
            'scripts' => [
                'label' => 'Mã tùy chỉnh',
                'description' => 'Chèn mã theo dõi hoặc widget vào website.',
                'keys' => [self::CUSTOM_SCRIPT_HEAD, self::CUSTOM_SCRIPT_BODY, self::CUSTOM_SCRIPT_FOOTER],
            ],
            'operation' => [
                'label' => 'Vận hành',
                'description' => 'Thiết lập quy trình bán hàng và phục vụ.',
                'keys' => [self::NO_SHOW_TIMEOUT, self::CUSTOMER_ORDERING, self::DELIVERY_FEE],
            ],
            'payment' => [
                'label' => 'Thanh toán VietQR',
                'description' => 'Thông tin tài khoản nhận chuyển khoản của nhà hàng.',
                'keys' => [self::VIETQR_BANK_ID, self::VIETQR_ACCOUNT_NUMBER, self::VIETQR_ACCOUNT_NAME, self::VIETQR_TRANSFER_PREFIX],
            ],
            'google_login' => [
                'label' => 'Đăng nhập Google',
                'description' => 'Kết nối tài khoản Google dành cho khách hàng.',
                'keys' => [self::GOOGLE_OAUTH_CLIENT_ID, self::GOOGLE_OAUTH_CLIENT_SECRET],
            ],
            'translation' => [
                'label' => 'Dịch tự động',
                'description' => 'Google Cloud Translation cho nội dung đa ngôn ngữ.',
                'keys' => [self::GOOGLE_TRANSLATION_PROJECT_ID, self::GOOGLE_TRANSLATION_CREDENTIALS],
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
}
