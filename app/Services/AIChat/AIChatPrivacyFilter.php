<?php

namespace App\Services\AIChat;

class AIChatPrivacyFilter
{
    public function redact(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', '[email]', $text) ?? '';
        $text = preg_replace('/(?<!\d)(?:\+?84|0)[\d .-]{8,14}(?!\d)/u', '[phone]', $text) ?? '';
        $text = preg_replace(
            '/\b(?:địa chỉ giao|delivery address|ship to|giao tới)\s*[:：-]?\s*[^\r\n]{1,300}/iu',
            '[delivery address]',
            $text,
        ) ?? '';
        $text = preg_replace(
            '/\b(?:mật khẩu|password|google id|số tài khoản|account number|bank account)\s*[:：-]?\s*\S+(?:\s+\S+){0,5}/iu',
            '[sensitive data]',
            $text,
        ) ?? '';

        return mb_substr($text, 0, 1500);
    }
}
