<?php

namespace App\Services\AIChat;

class FallbackIntentClassifier
{
    public function classify(string $message): AIChatIntent
    {
        $text = mb_strtolower($message);
        $owned = $this->contains($text, ['của tôi', 'my ', '我的']);

        return match (true) {
            preg_match('/ung thư|bị ung|dị ứng|ngộ độc|bệnh (?:gì|nào)|có thai.*ăn/u', $text) === 1 => AIChatIntent::Unsupported,
            $this->contains($text, ['nhân viên', 'khiếu nại', 'hoàn tiền', 'hủy đơn', 'staff', 'complaint', 'refund', '人工', '投诉', '退款']) => AIChatIntent::Unsupported,
            $this->contains($text, ['lịch đặt bàn', 'my reservation', '我的预订']) || ($owned && $this->contains($text, ['reservation', 'đặt bàn'])) => AIChatIntent::ReservationLookup,
            $this->contains($text, ['lịch sử đơn', 'my order', 'order history', '我的订单']) || ($owned && $this->contains($text, ['đơn', 'order'])) => AIChatIntent::OrderLookup,
            preg_match('/gợi ý.*(?:các|những) món|gợi ý.*món về/u', $text) === 1 => AIChatIntent::MenuSearch,
            $this->contains($text, ['gợi ý', 'đề xuất món', 'ăn gì', 'recommend', 'suggest', '推荐']) => AIChatIntent::Recommendation,
            $this->contains($text, ['giỏ', 'cart', '购物车']) => AIChatIntent::CartQuestion,
            $this->contains($text, ['voucher', 'mã giảm', 'coupon', '优惠券']) => AIChatIntent::VoucherGuide,
            $this->contains($text, ['thanh toán', 'vietqr', 'chuyển khoản', 'payment', 'pay', '支付']) => AIChatIntent::PaymentGuide,
            $this->contains($text, ['delivery', 'giao hàng', 'giao tận', '配送']) => AIChatIntent::DeliveryGuide,
            $this->contains($text, ['pickup', 'mang về', 'lấy món', '自取']) => AIChatIntent::PickupGuide,
            $this->contains($text, ['đặt bàn', 'reservation', 'reserve', '预订']) => AIChatIntent::ReservationGuide,
            $this->contains($text, ['đặt món', 'gọi món', 'order food', 'how to order', '点餐']) => AIChatIntent::OrderingGuide,
            $this->contains($text, ['đăng nhập', 'đăng ký', 'tài khoản', 'login', 'register', 'account', '登录', '注册']) => AIChatIntent::AccountHelp,
            $this->contains($text, ['giờ mở', 'mở cửa', 'opening hour', 'open time', '营业时间']) => AIChatIntent::OpeningHours,
            $this->contains($text, ['địa chỉ', 'bản đồ', 'google map', 'chỉ đường', 'hotline', 'email', 'liên hệ', 'zalo', 'facebook', 'messenger', 'address', 'contact', 'phone', '地图', '地址', '联系']) => AIChatIntent::ContactInfo,
            $this->contains($text, ['nhà hàng', 'beer garden', 'restaurant', '餐厅']) => AIChatIntent::RestaurantInfo,
            $this->contains($text, ['menu', 'thực đơn', 'món', 'bia', 'nước ngọt', 'đồ uống', 'giá', 'mực nướng', 'product', 'food', 'beer', 'soft drink', '菜单', '菜']) => AIChatIntent::MenuSearch,
            $this->contains($text, ['xin chào', 'chào', 'hello', 'hi', 'hey', '你好']) => AIChatIntent::Greeting,
            default => AIChatIntent::Unsupported,
        };
    }

    /** @return array<string,mixed> */
    public function extractRecommendationContext(string $message): array
    {
        $text = mb_strtolower($message);
        $context = [];
        if (preg_match('/(?:cho|for|为)?\s*(\d{1,2})\s*(?:người|people|persons|位|人)/u', $text, $matches) === 1) {
            $partySize = (int) $matches[1];
            if ($partySize >= 1 && $partySize <= 50) {
                $context['party_size'] = $partySize;
            }
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(triệu|tr|million|m)\b/u', $text, $matches) === 1) {
            $context['budget'] = (int) round((float) str_replace(',', '.', $matches[1]) * 1000000);
            $context['budget_skipped'] = false;
        } elseif (preg_match('/(\d+)\s*(?:k|nghìn|ngàn|thousand)\b/u', $text, $matches) === 1) {
            $context['budget'] = (int) $matches[1] * 1000;
            $context['budget_skipped'] = false;
        } elseif ($this->contains($text, [
            'không giới hạn',
            'không cần ngân sách',
            'không cần budget',
            'ngân sách thoải mái',
            'bao nhiêu cũng được',
            'no budget',
            'no limit',
            '不限预算',
        ])) {
            $context['budget_skipped'] = true;
        }
        $preferences = [];
        foreach ([
            'beer' => ['bia', 'beer', '啤酒'],
            'non_alcoholic' => ['không cồn', 'nước ngọt', 'soft drink', '无酒精'],
            'appetizer' => ['khai vị', 'appetizer', 'starter', '开胃菜'],
            'grilled' => ['nướng', 'grill', '烧烤'],
            'seafood' => ['hải sản', 'seafood', '海鲜'],
            'hotpot' => ['lẩu', 'hotpot', '火锅'],
        ] as $token => $keywords) {
            if ($this->contains($text, $keywords)) {
                $preferences[] = $token;
            }
        }
        if ($preferences !== []) {
            $context['preferences'] = $preferences;
        }

        return $context;
    }

    /** @param list<string> $needles */
    private function contains(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
