@extends('layouts.customer')
@section('title', 'Lịch sử đặt món')
@section('content')
    <div class="account-page">
        @include('customer.account._nav')
        <header class="account-heading"><div><h1>Lịch sử đặt món</h1><p>Theo dõi đơn nhận tại quán và giao tận nơi.</p></div></header>
        <form class="history-filters fulfillment-history-filters" method="get">
            <div><label class="form-label" for="order-q">Tìm kiếm</label><input class="form-control" id="order-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn"></div>
            <div><label class="form-label" for="order-type">Hình thức</label><select class="form-select" id="order-type" name="type"><option value="">Tất cả</option><option value="pickup" @selected(($filters['type'] ?? '') === 'pickup')>Nhận tại quán</option><option value="delivery" @selected(($filters['type'] ?? '') === 'delivery')>Giao tận nơi</option></select></div>
            <div><label class="form-label" for="order-status">Trạng thái đơn</label><select class="form-select" id="order-status" name="status"><option value="">Tất cả</option>@foreach(['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'rejected' => 'Đã từ chối'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label" for="order-payment">Thanh toán</label><select class="form-select" id="order-payment" name="payment"><option value="">Tất cả</option><option value="unpaid" @selected(($filters['payment'] ?? '') === 'unpaid')>Chưa thanh toán</option><option value="paid" @selected(($filters['payment'] ?? '') === 'paid')>Đã thanh toán</option></select></div>
            <button class="btn btn-primary" type="submit">Lọc</button>
            @if(request()->query())<a class="account-filter-clear" href="{{ route('customer.fulfillment-orders.index') }}">Xóa lọc</a>@endif
        </form>
        <div class="history-list">
            @forelse($orders as $order)
                @php
                    $statusClass = match($order->status) {'confirmed' => 'success', 'pending' => 'warning', default => 'secondary'};
                @endphp
                <article class="history-row fulfillment-history-row">
                    <div class="history-row-date"><strong>{{ $order->placed_at->format('d/m/Y') }}</strong><span>{{ $order->placed_at->format('H:i') }}</span></div>
                    <div class="history-row-main"><div><h2>{{ $order->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}</h2><small><x-display-code :code="$order->order_code" /></small></div><span class="status-badge text-bg-{{ $statusClass }}">{{ __('fulfillment_order.statuses.' . $order->status) }}</span></div>
                    <div class="history-row-meta"><span><small>Thời gian nhận</small><strong>{{ $order->requested_for->format('H:i · d/m/Y') }}</strong></span><span><small>Thanh toán</small><strong>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</strong></span><span class="history-row-total">{{ number_format($order->total_amount, 0, ',', '.') }} ₫</span></div>
                    <a class="history-row-link" href="{{ route('customer.fulfillment-orders.show', $order) }}">Xem chi tiết <span aria-hidden="true">›</span></a>
                </article>
            @empty
                <div class="empty-state"><span class="empty-state-mark" aria-hidden="true">◇</span><p>Chưa có đơn đặt món.</p></div>
            @endforelse
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
@endsection
