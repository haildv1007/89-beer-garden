@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Đơn ngoài quán')
@section('content')
    <div class="fulfillment-page ops-page">
        <header class="admin-section-heading fulfillment-heading ops-page-heading">
            <div>
                <h1>Đơn ngoài quán</h1>
                <p>Đơn khách đến lấy và giao tận nơi được quản lý tại đây. Món đặt trước theo bàn nằm trong Quản lý đặt bàn.
                </p>
            </div><a class="btn btn-primary"
                href="{{ route(($adminContext ?? false ? 'admin' : 'pos') . '.fulfillment-orders.create') }}">＋ Tạo đơn</a>
        </header>
        <section class="fulfillment-summary ops-summary" aria-label="Tổng quan đơn ngoài quán">
            <div><span>Chờ xử lý</span><strong>{{ $summary['pending'] }}</strong><small>Cần xác nhận hoặc từ chối</small>
            </div>
            <div><span>Nhận tại quán</span><strong>{{ $summary['pickup'] }}</strong><small>Khách tự đến lấy</small></div>
            <div><span>Giao tận nơi</span><strong>{{ $summary['delivery'] }}</strong><small>Có địa chỉ giao hàng</small>
            </div>
        </section>
        <form class="admin-list-filter fulfillment-filter ops-filter" method="get">
            <input class="form-control" name="q" value="{{ $search }}"
                placeholder="Tìm mã đơn, tên hoặc số điện thoại">
            <select class="form-select" name="type">
                <option value="">Tất cả hình thức</option>
                <option value="pickup" @selected(($filters['type'] ?? '') === 'pickup')>Nhận tại quán</option>
                <option value="delivery" @selected(($filters['type'] ?? '') === 'delivery')>Giao tận nơi</option>
            </select>
            <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ xác nhận</option>
                <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Đã xác nhận</option>
                <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Đã từ chối</option>
            </select>
            <button class="btn btn-primary">Tìm kiếm</button>
        </form>
        <div class="admin-data-shell fulfillment-list ops-data-shell">
            <table class="table align-middle admin-data-table ops-data-table">
                <thead>
                    <tr>
                        <th>Thời gian đặt</th>
                        <th>Đơn hàng</th>
                        <th>Khách nhận</th>
                        <th>Hình thức</th>
                        <th>Thời gian nhận</th>
                        <th>Số món</th>
                        <th>Tổng cộng</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong>{{ $order->placed_at->format('d/m/Y') }}</strong><small>{{ $order->placed_at->format('H:i') }}</small>
                            </td>
                            <td><strong><x-display-code :code="$order->order_code" /></strong></td>
                            <td><strong>{{ $order->customer_name }}</strong><small>{{ $order->phone }}</small></td>
                            <td><span
                                    @class([
                                        'fulfillment-type',
                                        'fulfillment-type--delivery' => $order->fulfillment_type === 'delivery',
                                    ])>{{ $order->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}</span>
                            </td>
                            <td><strong>{{ $order->requested_for->format('d/m/Y') }}</strong><small>{{ $order->requested_for->format('H:i') }}</small>
                            </td>
                            <td>{{ $order->items_count }}</td>
                            <td><strong>{{ number_format($order->total_amount) }} ₫</strong></td>
                            <td>
                                <span
                                    class="status-badge text-bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                                    {{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                </span>
                                <small>{{ $order->payment_option === 'bank_transfer' ? 'Chuyển khoản' : 'Thu khi nhận' }}</small>
                            </td>
                            @php
                                $statusClass = match ($order->status) {
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    default => 'secondary',
                                };
                            @endphp
                            <td>
                                <span class="status-badge text-bg-{{ $statusClass }}">
                                    {{ __('fulfillment_order.statuses.' . $order->status) }}
                                </span>
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary"
                                    href="{{ route($adminContext ?? false ? 'admin.fulfillment-orders.show' : 'pos.fulfillment-orders.show', $order) }}">Xem
                                    chi tiết</a></td>
                    </tr>@empty<tr>
                            <td colspan="10">
                                <div class="admin-empty-state">Chưa có đơn phù hợp với bộ lọc.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
@endsection
