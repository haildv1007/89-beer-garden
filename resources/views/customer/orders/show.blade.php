@extends('layouts.customer')
@section('title', 'Hóa đơn ' . $bill->bill_code . ' - ' . __('app.name'))
@section('content')
    @php
        $invoiceSettings = app(\App\Services\SystemSetting\TypedSystemSettingResolver::class)->publicSiteSettings();
        $invoiceSiteName = $invoiceSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_NAME] ?? 'Beer Garden';
        $invoiceLogoPath = $invoiceSettings[\App\Services\SystemSetting\SystemSettingCatalog::SITE_LOGO] ?? null;
        $invoiceLogoUrl = $invoiceLogoPath ? Storage::disk('public')->url($invoiceLogoPath) : asset('images/brand/quan-89-logo.png');
    @endphp
    <div class="account-page customer-invoice-page">
        @include('customer.account._nav')
        <div class="customer-invoice-actions no-print">
            <a class="btn btn-outline-primary" href="{{ route('customer.orders.history') }}">Lịch sử thanh toán</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">In hóa đơn</button>
        </div>
        <article class="customer-invoice-sheet">
            <header><img src="{{ $invoiceLogoUrl }}" alt="{{ $invoiceSiteName }}">
                <div><span>{{ mb_strtoupper($invoiceSiteName) }}</span><h1>HÓA ĐƠN THANH TOÁN</h1><p><x-display-code :code="$bill->bill_code" /></p></div>
            </header>
            <section class="customer-invoice-meta">
                <div><span>Bàn</span><strong>{{ $bill->diningSession->table?->name ?: 'Tại quán' }}</strong></div>
                <div><span>Thanh toán lúc</span><strong>{{ $bill->successfulPayment->paid_at->format('d/m/Y H:i') }}</strong></div>
                <div><span>Phương thức</span><strong>{{ __('billing.methods.' . $bill->successfulPayment->method) }}</strong></div>
                <div><span>Mã thanh toán</span><strong>{{ $bill->successfulPayment->payment_code }}</strong></div>
            </section>
            <section class="customer-invoice-lines">
                @foreach ($bill->diningSession->orders as $order)
                    @foreach ($order->items as $item)
                        <div><span><strong>{{ $item->product_name }}@if($item->variant_name) — {{ $item->variant_name }}@endif</strong><small>{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }} ₫</small></span>
                            <strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong></div>
                    @endforeach
                @endforeach
            </section>
            <dl class="customer-invoice-totals">
                <div><dt>Tạm tính</dt><dd>{{ number_format($bill->subtotal, 0, ',', '.') }} ₫</dd></div>
                <div><dt>Giảm giá{{ $bill->voucher ? ' (' . $bill->voucher->code . ')' : '' }}</dt><dd>− {{ number_format($bill->discount_amount, 0, ',', '.') }} ₫</dd></div>
                <div class="is-total"><dt>Tổng thanh toán</dt><dd>{{ number_format($bill->total_amount, 0, ',', '.') }} ₫</dd></div>
                @if ($bill->successfulPayment->method === \App\Models\Payment::METHOD_CASH)
                    <div><dt>Tiền khách đưa</dt><dd>{{ number_format($bill->successfulPayment->received_amount, 0, ',', '.') }} ₫</dd></div>
                    <div><dt>Tiền trả lại</dt><dd>{{ number_format($bill->successfulPayment->change_amount, 0, ',', '.') }} ₫</dd></div>
                @endif
            </dl>
            <footer>Cảm ơn quý khách và hẹn gặp lại!</footer>
        </article>
    </div>
@endsection
