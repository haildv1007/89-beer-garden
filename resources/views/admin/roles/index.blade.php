@extends('layouts.admin')
@section('title', __('employee.roles.title'))
@section('content')
    <div class="role-management-page">
        @php
            $roleHelp = [
                'admin' => [
                    'Quản trị toàn bộ hệ thống',
                    'Có toàn quyền cấu hình, nhân sự, vận hành và dữ liệu. Chỉ nên cấp cho chủ cửa hàng hoặc quản trị viên chính.',
                ],
                'manager' => [
                    'Quản lý cửa hàng',
                    'Theo dõi vận hành, đặt bàn, đơn hàng và báo cáo; không mặc định có toàn bộ quyền hệ thống.',
                ],
                'staff' => [
                    'Nhân viên phục vụ',
                    'Xử lý bàn, đặt bàn, phiên phục vụ và đơn tại quán trong ca làm việc.',
                ],
                'kitchen' => ['Nhân viên bếp', 'Xem hàng đợi bếp và cập nhật trạng thái chế biến món.'],
                'customer' => [
                    'Khách hàng',
                    'Chỉ sử dụng các chức năng cá nhân trên website như hồ sơ, lịch sử đơn và đặt bàn.',
                ],
            ];
            $permissionGroups = [
                'Truy cập khu vực' => ['context.'],
                'Bàn và đặt bàn' => ['restaurant-table.', 'table.', 'reservation.', 'dining-session.'],
                'Đơn hàng, bếp và thanh toán' => [
                    'order.',
                    'order-item.',
                    'kitchen.',
                    'payment.',
                    'billing.',
                    'voucher.',
                ],
                'Thực đơn và nội dung' => ['category.', 'product.', 'post.'],
                'Khách hàng và nhân sự' => ['customer.', 'employee.', 'permission.'],
                'Báo cáo và cấu hình' => ['report.', 'settings.', 'translation.'],
            ];
        @endphp
        <header class="admin-page-header">
            <div><span class="admin-page-eyebrow">Phân quyền nhân sự</span>
                <h1>{{ __('employee.roles.title') }}</h1>
                <p>Vai trò quyết định mỗi loại tài khoản được nhìn thấy và thao tác ở đâu. Chọn một vai trò bên dưới để xem
                    hoặc điều chỉnh quyền chi tiết.</p>
            </div>
        </header>
        <div class="role-overview" aria-label="Tổng quan vai trò">
            @foreach ($roles as $role)
                <a href="#role-{{ $role->id }}"><strong>{{ $role->name }}</strong><span>{{ $roleHelp[$role->code][0] ?? $role->description }}</span><small>{{ $role->users_count }}
                        tài khoản · {{ $role->permissions->count() }} quyền</small></a>
            @endforeach
        </div>
        <div class="role-notice"><strong>Cách dùng:</strong> gán vai trò cho nhân viên ở trang <a
                href="{{ route('admin.employees.index') }}">Nhân viên</a>. Chỉ chỉnh các ô quyền bên dưới khi quy trình cửa
            hàng thực sự cần khác mặc định.</div>
        <div class="accordion role-matrix" id="role-matrix">
            @foreach ($roles as $role)
                <div class="accordion-item" id="role-{{ $role->id }}">
                    <h2 class="accordion-header"><button
                            class="accordion-button @if (!$loop->first) collapsed @endif" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#role-panel-{{ $role->id }}"><span><strong>{{ $role->name }}</strong><small>{{ $roleHelp[$role->code][0] ?? $role->description }}
                                    · {{ $role->permissions->count() }} quyền đang bật</small></span></button></h2>
                    <div id="role-panel-{{ $role->id }}"
                        class="accordion-collapse collapse @if ($loop->first) show @endif"
                        data-bs-parent="#role-matrix">
                        <div class="accordion-body">
                            <p class="role-description">{{ $roleHelp[$role->code][1] ?? $role->description }}</p>
                            <form method="post" action="{{ route('admin.roles.permissions.update', $role) }}">@csrf
                                @method('put')
                                <div class="permission-groups">
                                    @foreach ($permissionGroups as $groupName => $prefixes)
                                        @php
                                            $groupPermissions = $permissions->filter(
                                                fn($permission) => collect($prefixes)->contains(
                                                    fn($prefix) => str_starts_with($permission->code, $prefix),
                                                ),
                                            );
                                        @endphp
                                        @if ($groupPermissions->isNotEmpty())
                                            <fieldset class="permission-group">
                                                <legend>{{ $groupName }}</legend>
                                                <div class="permission-grid">
                                                    @foreach ($groupPermissions as $permission)
                                                        <label class="permission-option"
                                                            for="r{{ $role->id }}-p{{ $permission->id }}"><input
                                                                type="checkbox"
                                                                id="r{{ $role->id }}-p{{ $permission->id }}"
                                                                name="permissions[]" value="{{ $permission->id }}"
                                                                @checked($role->permissions->contains($permission))>
                                                            <span>
                                                                <strong>{{ $permission->name }}</strong>
                                                                <small>{{ $permission->code }}</small>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </fieldset>
                                        @endif
                                    @endforeach
                                </div>
                                <button class="btn btn-primary mt-3">Lưu quyền của {{ $role->name }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
