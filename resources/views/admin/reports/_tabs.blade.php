<nav class="nav nav-pills report-tabs gap-2 mb-4" aria-label="Các mục báo cáo">
    <a @class(['nav-link', 'active' => request()->routeIs('admin.reports.index')]) href="{{ route('admin.reports.index') }}">Tổng quan vận hành</a>
    <a @class(['nav-link', 'active' => request()->routeIs('admin.reports.payments')]) href="{{ route('admin.reports.payments') }}">Lịch sử thanh toán</a>
</nav>
