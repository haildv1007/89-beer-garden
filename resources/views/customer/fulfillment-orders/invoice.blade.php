@extends('layouts.customer')
@section('title', 'Hóa đơn ' . $order->order_code)
@section('content')
    @php
        $invoiceSettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
        $invoiceSiteName = $invoiceSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden';
        $invoiceLogoPath = $invoiceSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_LOGO] ?? null;
        $invoiceLogoUrl = $invoiceLogoPath ? Storage::disk('public')->url($invoiceLogoPath) : asset('images/brand/quan-89-logo.png');
    @endphp
    <div class="account-page customer-invoice-page">
        @include('customer.account._nav')
        <div class="customer-invoice-actions no-print"><a class="btn btn-outline-primary" href="{{ route('customer.orders.history') }}">Lịch sử thanh toán</a><button class="btn btn-primary" type="button" onclick="window.print()">In hóa đơn</button></div>
        <article class="customer-invoice-sheet">
            <header><img src="{{ $invoiceLogoUrl }}" alt="{{ $invoiceSiteName }}"><div><span>{{ mb_strtoupper($invoiceSiteName) }}</span><h1>HÓA ĐƠN BÁN HÀNG</h1><p><x-display-code :code="$order->order_code" /></p></div></header>
            <section class="customer-invoice-meta"><div><span>Hình thức</span><strong>{{ $order->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}</strong></div><div><span>Thanh toán lúc</span><strong>{{ $order->paid_at->format('d/m/Y H:i') }}</strong></div><div><span>Khách hàng</span><strong>{{ $order->customer_name }}</strong></div><div><span>Phương thức</span><strong>{{ __('billing.methods.' . $order->payment_method) }}</strong></div></section>
            <section class="customer-invoice-lines">@foreach($order->items as $item)<div><span><strong>{{ $item->product_name }}@if($item->variant_name) · {{ $item->variant_name }}@endif</strong><small>{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }} ₫</small></span><strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong></div>@endforeach</section>
            <dl class="customer-invoice-totals"><div><dt>Tạm tính</dt><dd>{{ number_format($order->subtotal, 0, ',', '.') }} ₫</dd></div><div><dt>Giảm giá{{ $order->voucher ? ' (' . $order->voucher->code . ')' : '' }}</dt><dd>− {{ number_format($order->discount_amount, 0, ',', '.') }} ₫</dd></div>@if($order->fulfillment_type === 'delivery')<div><dt>Phí giao hàng</dt><dd>{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</dd></div>@endif<div class="is-total"><dt>Tổng thanh toán</dt><dd>{{ number_format($order->total_amount, 0, ',', '.') }} ₫</dd></div>@if($order->payment_method === 'cash')<div><dt>Tiền khách đưa</dt><dd>{{ number_format($order->received_amount, 0, ',', '.') }} ₫</dd></div><div><dt>Tiền trả lại</dt><dd>{{ number_format($order->change_amount, 0, ',', '.') }} ₫</dd></div>@endif</dl>
            <footer>Cảm ơn quý khách và hẹn gặp lại!</footer>
        </article>
    </div>
@endsection
