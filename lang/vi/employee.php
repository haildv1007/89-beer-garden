<?php

return [
    'employees' => [
        'title' => 'Nhân viên', 'create' => 'Tạo nhân viên', 'edit' => 'Sửa nhân viên',
        'empty' => 'Chưa có nhân viên.', 'disabled_success' => 'Đã vô hiệu hóa nhân viên và tài khoản liên kết.',
        'disable' => 'Vô hiệu hóa', 'disable_confirm' => 'Vô hiệu hóa nhân viên và tài khoản liên kết?',
        'admin_disable_forbidden' => 'Manager không được vô hiệu hóa tài khoản Admin.',
        'last_admin_protected' => 'Không thể vô hiệu hóa Admin active cuối cùng.',
    ],
    'accounts' => [
        'title' => 'Tài khoản liên kết', 'none' => 'Nhân viên này chưa có tài khoản.',
        'create' => 'Tạo tài khoản', 'created' => 'Đã tạo tài khoản liên kết.',
        'already_linked' => 'Nhân viên đã có tài khoản liên kết.', 'employee_disabled' => 'Không thể tạo tài khoản cho nhân viên đã vô hiệu hóa.', 'invalid_role' => 'Vai trò không hợp lệ cho tài khoản nhân viên.', 'role_updated' => 'Đã cập nhật vai trò.',
    ],
    'roles' => [
        'title' => 'Vai trò và quyền', 'current' => 'Vai trò hiện tại', 'update' => 'Cập nhật vai trò',
        'permissions_updated' => 'Đã cập nhật ma trận quyền.', 'matrix_help' => 'Chỉ các quyền trong catalog được duyệt có thể được gán.',
        'admin_mandatory_permissions' => 'Role Admin phải giữ context.admin.access và permission.assign.',
    ],
    'permissions' => ['title' => 'Quyền'],
    'fields' => [
        'employee_code' => 'Mã nhân viên', 'name' => 'Họ tên', 'phone' => 'Số điện thoại',
        'position' => 'Vị trí', 'status' => 'Trạng thái', 'email' => 'Email',
        'password' => 'Mật khẩu', 'password_confirmation' => 'Xác nhận mật khẩu', 'role' => 'Vai trò',
    ],
    'statuses' => ['active' => 'Đang hoạt động', 'disabled' => 'Đã vô hiệu hóa'],
    'filters' => ['all_statuses' => 'Tất cả trạng thái', 'all_roles' => 'Tất cả vai trò'],
    'bootstrap_admin' => [
        'employee_code' => 'Mã nhân viên', 'employee_name' => 'Tên nhân viên', 'email' => 'Email quản trị',
        'password' => 'Mật khẩu', 'password_confirmation' => 'Xác nhận mật khẩu',
        'role_missing' => 'Chưa có role admin. Hãy chạy seeder trước.',
        'already_exists' => 'Tài khoản Admin ban đầu đã tồn tại.', 'created' => 'Đã tạo Admin ban đầu an toàn.', 'failed' => 'Không thể tạo Admin; không có dữ liệu nào được lưu.',
    ],
];
