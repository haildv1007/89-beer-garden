<?php

return [
    'open' => 'Mở thanh toán', 'view_bill' => 'Xem hóa đơn', 'refresh' => 'Tính lại hóa đơn',
    'refreshed' => 'Hóa đơn đã được tạo hoặc tính lại.', 'voucher_applied' => 'Đã áp dụng voucher.',
    'voucher_removed' => 'Đã gỡ voucher.', 'payment_completed' => 'Thanh toán hoàn tất.',
    'payment_failed_recorded' => 'Đã ghi nhận lần thanh toán thất bại.', 'invoice' => 'Hóa đơn', 'print' => 'In hóa đơn',
    'bill_code' => 'Mã hóa đơn', 'session' => 'Phiên phục vụ', 'table' => 'Bàn', 'customer' => 'Khách hàng',
    'product' => 'Món', 'quantity' => 'Số lượng', 'unit_price' => 'Đơn giá lịch sử', 'line_total' => 'Thành tiền',
    'item_status' => 'Trạng thái món', 'subtotal' => 'Tạm tính', 'voucher' => 'Voucher', 'discount' => 'Giảm giá',
    'total' => 'Tổng thanh toán', 'status' => 'Trạng thái', 'voucher_code' => 'Nhập mã voucher',
    'apply_voucher' => 'Áp dụng', 'remove_voucher' => 'Gỡ voucher', 'complete_payment' => 'Xác nhận thanh toán',
    'confirm_received' => 'Tôi xác nhận nhà hàng đã nhận đủ tiền', 'record_failure' => 'Ghi nhận thất bại',
    'failure_reason' => 'Lý do thất bại', 'attempts' => 'Các lần thanh toán', 'payment_code' => 'Mã thanh toán',
    'method' => 'Phương thức', 'amount' => 'Số tiền', 'processed_by' => 'Nhân viên xử lý', 'time' => 'Thời gian',
    'reference' => 'Mã tham chiếu', 'paid_at' => 'Thanh toán lúc', 'no_attempts' => 'Chưa có lần thanh toán nào.',
    'methods' => ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản ngân hàng', 'other' => 'Khác'],
    'statuses' => ['draft' => 'Nháp', 'unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'cancelled' => 'Đã hủy'],
    'payment_statuses' => ['pending' => 'Đang chờ', 'success' => 'Thành công', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy'],
    'errors' => [
        'empty' => 'Phiên phục vụ chưa có món hợp lệ để tính tiền.', 'overflow' => 'Tổng tiền vượt giới hạn hệ thống.',
        'session_inactive' => 'Chỉ có thể mở hóa đơn cho phiên đang hoạt động.', 'paid' => 'Hóa đơn đã thanh toán và không thể tính lại.',
        'paid_or_inactive' => 'Hóa đơn đã thanh toán hoặc phiên không còn hoạt động.',
        'voucher_invalid' => 'Voucher không tồn tại hoặc không còn đáp ứng điều kiện.',
        'zero_total' => 'Voucher làm tổng tiền bằng 0 nên không thể thanh toán theo cấu trúc hiện tại.',
        'duplicate_payment' => 'Hóa đơn đã có một thanh toán thành công.', 'table_invalid' => 'Bàn không còn ở trạng thái phục vụ hợp lệ.',
        'reservation_invalid' => 'Trạng thái đặt bàn không hợp lệ để hoàn tất phiên.',
    ],
];
