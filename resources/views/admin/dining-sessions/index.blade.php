@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Phiên phục vụ')
@section('content')
    <div class="ops-page">
        <header class="admin-section-heading dining-list-heading ops-page-heading">
            <div>
                <h1>Phiên phục vụ</h1>
                <p>Theo dõi bàn đang phục vụ, món đã gọi và giá trị tạm tính của từng phiên.</p>
            </div>
        </header>

        <form class="admin-list-filter admin-list-filter--sessions ops-filter" method="get">
            <input class="form-control" name="q" value="{{ $search }}"
                placeholder="Tìm mã phiên, bàn, khách hàng hoặc số điện thoại">
            <select class="form-select" name="status">
                <option value="active" @selected($status === 'active')>Đang phục vụ</option>
                <option value="completed" @selected($status === 'completed')>Đã hoàn tất</option>
                <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
            </select>
            <button class="btn btn-primary">Tìm kiếm</button>
        </form>

        <div class="admin-data-shell dining-session-list ops-data-shell">
            <table class="table align-middle admin-data-table ops-data-table">
                <thead>
                    <tr>
                        <th>Bắt đầu</th>
                        <th>Mã phiên</th>
                        <th>Bàn</th>
                        <th>Khách hàng</th>
                        <th>Số khách</th>
                        <th>Lượt gọi món</th>
                        <th>Giá trị món</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td><strong>{{ $session->started_at->format('d/m/Y') }}</strong><small>{{ $session->started_at->format('H:i') }}</small>
                            </td>
                            <td><strong><x-display-code :code="$session->session_code" /></strong></td>
                            <td><strong>{{ $session->table->name }}</strong><small>{{ $session->table->code }}</small></td>
                            <td><strong>{{ $session->customer?->name ?: 'Khách không định danh' }}</strong><small>{{ $session->customer?->phone ?: 'Chưa có số điện thoại' }}</small>
                            </td>
                            <td><strong>{{ $session->guest_count }}</strong></td>
                            <td><strong>{{ $session->orders_count }}</strong></td>
                            <td><strong class="dining-money">{{ number_format($session->order_total) }} ₫</strong></td>
                            @php
                                $statusClass = $session->status->value === 'active' ? 'warning' : 'success';
                            @endphp
                            <td>
                                <span class="status-badge text-bg-{{ $statusClass }}">
                                    {{ __('dining_session.statuses.' . $session->status->value) }}
                                </span>
                            </td>
                            <td class="text-end"><a class="btn btn-outline-primary"
                                    href="{{ route($adminContext ?? false ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show', $session) }}">Xem
                                    phiên</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="admin-empty-state">Chưa có phiên phù hợp.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </div>
@endsection
