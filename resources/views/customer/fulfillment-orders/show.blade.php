@extends('layouts.customer')
@section('title', 'Chi tiết đơn ' . $order->order_code)
@section('content')
    @php
        $typeLabel = $order->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán';
        $statusClass = match($order->status) {'confirmed' => 'success', 'pending' => 'warning', default => 'secondary'};
    @endphp
    <div class="account-page">
        @include('customer.account._nav')
        <header class="account-heading account-heading--detail fulfillment-detail-heading"><div><h1>Chi tiết đơn đặt món</h1><p class="reservation-detail-code">Mã đơn <span>{{ $order->order_code }}</span></p></div><span class="status-badge text-bg-{{ $statusClass }}">{{ __('fulfillment_order.statuses.' . $order->status) }}</span></header>
        <section class="reservation-detail fulfillment-customer-detail">
            <div class="reservation-detail-grid fulfillment-detail-overview">
                <div><span>Hình thức</span><strong>{{ $typeLabel }}</strong></div>
                <div><span>Thời gian nhận</span><strong>{{ $order->requested_for->format('H:i · d/m/Y') }}</strong></div>
                <div><span>Thanh toán</span><strong>{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</strong></div>
            </div>
            @if($order->fulfillment_type === 'delivery')<div class="fulfillment-customer-address"><span>Địa chỉ giao hàng</span><strong>{{ $order->delivery_address }}</strong></div>@endif
            @if($order->status === 'rejected' && $order->rejection_reason)<div class="alert alert-warning mt-3 mb-0"><strong>Lý do từ chối:</strong> {{ $order->rejection_reason }}</div>@endif
            <div class="fulfillment-detail-body">
                <div class="customer-order-lines"><div class="customer-order-lines-heading"><h2>Món đã đặt</h2><span>{{ $order->items->sum('quantity') }} phần</span></div>@foreach($order->items as $item)<div><span><strong>{{ $item->product_name }}@if($item->variant_name) · {{ $item->variant_name }}@endif</strong><small>{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }} ₫</small></span><strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong></div>@endforeach</div>
                <aside class="fulfillment-detail-payment"><h2>Tổng đơn</h2><dl class="customer-invoice-totals"><div><dt>Tạm tính</dt><dd>{{ number_format($order->subtotal, 0, ',', '.') }} ₫</dd></div><div><dt>Giảm giá</dt><dd>− {{ number_format($order->discount_amount, 0, ',', '.') }} ₫</dd></div>@if($order->fulfillment_type === 'delivery')<div><dt>Phí giao hàng</dt><dd>{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</dd></div>@endif<div class="is-total"><dt>Tổng cộng</dt><dd>{{ number_format($order->total_amount, 0, ',', '.') }} ₫</dd></div></dl>@if($order->payment_status === 'paid')<div class="fulfillment-customer-actions"><a class="btn btn-primary" href="{{ route('customer.orders.history.fulfillment', $order) }}">Xem hóa đơn</a></div>@endif</aside>
            </div>
        </section>
    </div>
@endsection
