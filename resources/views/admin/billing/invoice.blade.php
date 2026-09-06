@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Hóa đơn ' . $bill->bill_code)
@section('content')
    <div class="invoice-actions no-print">
        <a class="admin-back-link" href="{{ route(($adminContext ?? false ? 'admin' : 'pos') . '.bills.show', $bill) }}">←
            Quay lại thanh toán</a><button class="btn btn-primary" type="button" onclick="window.print()">In hóa đơn</button>
    </div>
    <article class="admin-invoice-sheet">
        <header><img src="{{ asset('images/brand/quan-89-logo.png') }}" alt="89 Beer Garden">
            <div><span>89 BEER GARDEN</span>
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
                    <div><strong>{{ $item->product_name }}</strong><span>{{ $item->quantity }} ×
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
        <footer>
            <p>{{ __('billing.methods.' . $bill->successfulPayment->method) }}{{ $bill->successfulPayment->transaction_reference ? ' · ' . $bill->successfulPayment->transaction_reference : '' }}
            </p><strong>Cảm ơn quý khách và hẹn gặp lại!</strong>
        </footer>
    </article>
@endsection
