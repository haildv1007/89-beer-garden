<?php

return [
    'title' => 'Voucher', 'create' => 'Tạo voucher', 'search' => 'Tìm theo mã hoặc tên', 'all_statuses' => 'Tất cả trạng thái',
    'empty' => 'Chưa có voucher.', 'historical_locked' => 'Voucher đã liên kết hóa đơn nên dữ liệu lịch sử bị khóa.',
    'fields' => ['code' => 'Mã', 'name' => 'Tên', 'type' => 'Loại giảm', 'value' => 'Giá trị', 'max_discount' => 'Giảm tối đa', 'minimum' => 'Đơn tối thiểu', 'start' => 'Bắt đầu', 'end' => 'Kết thúc', 'usage_limit' => 'Giới hạn lượt dùng', 'used' => 'Đã dùng', 'status' => 'Trạng thái'],
    'types' => ['fixed' => 'Số tiền cố định', 'percentage' => 'Phần trăm (0–100)'],
    'statuses' => ['active' => 'Hoạt động', 'inactive' => 'Không hoạt động'],
    'errors' => ['historical' => 'Không thể sửa hoặc xóa voucher đã liên kết với hóa đơn.'],
];
