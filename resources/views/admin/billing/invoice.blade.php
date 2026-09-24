@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Hóa đơn ' . $bill->bill_code)
@section('content')
    <div class="invoice-actions no-print">
        <button class="btn btn-primary" type="button" onclick="window.print()">In hóa đơn</button>
    </div>
    <article class="admin-invoice-sheet">
        <header><img src="{{ $configuredSiteLogoUrl }}" alt="{{ $configuredSiteName }}">
            <div><span>{{ mb_strtoupper($configuredSiteName) }}</span>
                <h1>HÓA ĐƠN THANH TOÁN</h1>
                <p><x-display-code :code="$bill->bill_code" /></p>
            </div>
        </header>
        <section class="admin-invoice-meta">
            <div><span>Bàn</span><strong>{{ $bill->diningSession->table->name }}</strong></div>
            <div><span>Khách
                    hàng</span><strong>{{ $bill->diningSession->customer?->name ?? __('dining_session.anonymous') }}</strong>
            </div>
            <div><span>Thanh toán lúc</span><strong>{{ $bill->successfulPayment->paid_at->format('d/m/Y H:i') }}</strong>
            </div>
            <div><span>Thu ngân</span><strong>{{ $bill->successfulPayment->processedBy->name }}</strong></div>
        </section>
        @foreach ($bill->diningSession->orders as $order)
            @foreach ($order->items as $item)
                <div class="admin-invoice-line">
                    <div><strong>{{ $item->product_name }}@if($item->variant_name) — {{ $item->variant_name }}@endif</strong><span>{{ $item->quantity }} ×
                            {{ number_format($item->unit_price, 0, ',', '.') }} ₫</span></div>
                    <strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong>
                </div>
            @endforeach
        @endforeach
        <dl class="admin-invoice-totals">
            <div>
                <dt>Tạm tính</dt>
                <dd>{{ number_format($bill->subtotal, 0, ',', '.') }} ₫</dd>
            </div>
            <div>
                <dt>Giảm giá {{ $bill->voucher ? '(' . $bill->voucher->code . ')' : '' }}</dt>
                <dd>− {{ number_format($bill->discount_amount, 0, ',', '.') }} ₫</dd>
            </div>
            <div class="total">
                <dt>Tổng thanh toán</dt>
                <dd>{{ number_format($bill->total_amount, 0, ',', '.') }} ₫</dd>
            </div>
            @if ($bill->successfulPayment->method === \App\Models\Payment::METHOD_CASH)
                <div>
                    <dt>Tiền khách đưa</dt>
                    <dd>{{ number_format($bill->successfulPayment->received_amount, 0, ',', '.') }} ₫</dd>
                </div>
                <div>
                    <dt>Tiền trả lại</dt>
                    <dd>{{ number_format($bill->successfulPayment->change_amount, 0, ',', '.') }} ₫</dd>
                </div>
            @endif
        </dl>
        <section class="admin-invoice-payment">
            <div>
                <span>Phương thức</span>
                <strong>{{ __('billing.methods.' . $bill->successfulPayment->method) }}</strong>
            </div>
            <div>
                <span>Mã thanh toán</span>
                <strong>{{ $bill->successfulPayment->payment_code }}</strong>
            </div>
            @if ($bill->successfulPayment->method === \App\Models\Payment::METHOD_BANK_TRANSFER)
                <div>
                    <span>Nội dung chuyển khoản</span>
                    <strong>{{ $bill->payment_reference ?: '—' }}</strong>
                </div>
                <div>
                    <span>Mã giao dịch ngân hàng</span>
                    <strong>{{ $bill->successfulPayment->transaction_reference ?: '—' }}</strong>
                </div>
            @endif
        </section>
        <footer>
            <strong>Cảm ơn quý khách và hẹn gặp lại!</strong>
        </footer>
    </article>
@endsection
