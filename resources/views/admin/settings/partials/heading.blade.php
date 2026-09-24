<header class="settings-heading">
    <div>
        <span class="admin-page-eyebrow">HỆ THỐNG</span>
        <h1>{{ __('setting.title') }}</h1>
        <p>Quản lý cấu hình website và hệ thống.</p>
    </div>
    @can('permission.assign')
        <a class="btn btn-outline-primary" href="{{ route('admin.roles.index') }}">Vai trò và quyền</a>
    @endcan
</header>
