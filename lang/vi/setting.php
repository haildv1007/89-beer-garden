<?php

return [
    'title' => 'Cấu hình hệ thống',
    'intro' => 'Chỉ các cấu hình vận hành được phê duyệt mới có thể chỉnh tại đây. Thông tin bí mật và môi trường không được quản lý qua giao diện.',
    'valid' => 'Hợp lệ',
    'invalid' => 'Không hợp lệ',
    'type' => 'Kiểu dữ liệu',
    'current_value' => 'Giá trị hiện tại',
    'updated_by' => 'Cập nhật bởi',
    'updated_at' => 'Cập nhật lúc',
    'new_value' => 'Giá trị mới',
    'missing' => 'Chưa thiết lập',
    'enabled' => 'Bật',
    'disabled' => 'Tắt',
    'save' => 'Lưu cấu hình',
    'saved' => 'Đã lưu cấu hình.',
    'types' => ['integer' => 'Số nguyên', 'boolean' => 'Boolean', 'string' => 'Văn bản'],
    'items' => [
        'no_show_timeout_minutes' => [
            'label' => 'Thời gian chờ no-show',
            'description' => 'Số phút từ giờ đặt bàn trước khi reservation đã xác nhận được phép chuyển thành no-show (1–1440 phút).',
        ],
        'customer_ordering_enabled' => [
            'label' => 'Khách tự gọi món',
            'description' => 'Khi tắt, liên kết bàn đã ký, giỏ hàng và luồng khách tự gọi món sẽ không sử dụng được.',
        ],
        'delivery_fee' => [
            'label' => 'Phí giao hàng',
            'description' => 'Phí giao tận nơi mặc định, tính bằng VND (0–10.000.000). Đơn đã tạo giữ nguyên mức phí tại thời điểm đặt.',
        ],
        'vietqr_bank_id' => [
            'label' => 'Ngân hàng nhận VietQR',
            'description' => 'Mã ngân hàng, ví dụ MB, VCB hoặc BIDV.',
        ],
        'vietqr_account_number' => [
            'label' => 'Số tài khoản nhận tiền',
            'description' => 'Số tài khoản ngân hàng của nhà hàng.',
        ],
        'vietqr_account_name' => [
            'label' => 'Tên chủ tài khoản',
            'description' => 'Tên chủ tài khoản hiển thị trên mã chuyển khoản.',
        ],
        'vietqr_transfer_prefix' => [
            'label' => 'Tiền tố chuyển khoản',
            'description' => 'Tiền tố ngắn ghép với mã đơn, ví dụ BG.',
        ],
    ],
    'validation' => [
        'invalid' => 'Giá trị cấu hình không hợp lệ hoặc key không được phép.',
        'actor_inactive' => 'Tài khoản hoặc hồ sơ nhân viên không còn hoạt động.',
    ],
];
