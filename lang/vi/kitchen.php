<?php

return [
    'title' => 'Hàng đợi bếp',
    'refresh' => 'Làm mới',
    'manual_refresh_note' => 'Dữ liệu cập nhật thủ công để giữ nhịp bếp ổn định.',
    'wait_minutes' => ':count phút',
    'empty' => 'Không có món trong khu vực này.',
    'quantity' => 'Số lượng: :count',
    'actions' => 'Thao tác',
    'start_preparing' => 'Bắt đầu chế biến',
    'mark_ready' => 'Đánh dấu sẵn sàng',
    'mark_served' => 'Đã phục vụ',
    'cancel_item' => 'Hủy món',
    'cancellation_reason' => 'Lý do hủy',
    'started' => 'Món đã chuyển sang đang chế biến.',
    'ready' => 'Món đã sẵn sàng.',
    'served' => 'Món đã được đánh dấu phục vụ.',
    'cancelled' => 'Món đã được hủy.',
    'sections' => ['waiting' => 'Đang chờ', 'preparing' => 'Đang chế biến', 'ready' => 'Sẵn sàng phục vụ'],
    'errors' => [
        'transition_invalid' => 'Trạng thái món hoặc phiên phục vụ không còn hợp lệ cho thao tác này.',
        'cancellation_invalid' => 'Món không còn ở trạng thái được phép hủy hoặc phiên phục vụ đã kết thúc.',
    ],
];
