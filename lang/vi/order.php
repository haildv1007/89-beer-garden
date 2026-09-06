<?php

return [
    'create' => 'Gọi món',
    'additional' => 'Gọi thêm món',
    'submit' => 'Tạo lượt gọi món',
    'created' => 'Đã tạo lượt gọi món.',
    'item_updated' => 'Đã cập nhật món đang chờ.',
    'history' => 'Các lượt gọi món',
    'empty' => 'Chưa có lượt gọi món.',
    'empty_products' => 'Không có món hợp lệ để gọi.',
    'search_products' => 'Tìm theo tên món hoặc danh mục',
    'selected_products' => 'món đã chọn',
    'subtotal' => 'Tạm tính',
    'fields' => [
        'product' => 'Món',
        'price' => 'Đơn giá lịch sử',
        'quantity' => 'Số lượng',
        'line_total' => 'Thành tiền',
        'status' => 'Trạng thái',
        'order_note' => 'Ghi chú lượt gọi',
        'item_note' => 'Ghi chú món',
    ],
    'sources' => ['staff' => 'POS', 'customer' => 'Khách hàng'],
    'statuses' => [
        'waiting' => 'Đang chờ',
        'preparing' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'served' => 'Đã phục vụ',
        'cancelled' => 'Đã hủy',
    ],
    'errors' => [
        'session_invalid' => 'Phiên phục vụ hoặc trạng thái bàn không còn hợp lệ để gọi món.',
        'product_invalid' => 'Một hoặc nhiều món không còn hoạt động, sẵn sàng hoặc thuộc danh mục hoạt động.',
        'waiting_required' => 'Chỉ món đang chờ trong phiên hoạt động mới được chỉnh sửa.',
        'quantity_overflow' => 'Số lượng làm thành tiền vượt giới hạn lưu trữ.',
    ],
];
