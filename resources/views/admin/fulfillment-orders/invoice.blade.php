@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Hóa đơn ' . $fulfillmentOrder->order_code)
@section('content')
    @php
        $fulfillmentRoutes = $adminContext ?? false ? 'admin.fulfillment-orders' : 'pos.fulfillment-orders';
    @endphp
    <div class="invoice-actions no-print"><a class="admin-back-link"
            href="{{ route($fulfillmentRoutes . '.show', $fulfillmentOrder) }}">← Quay lại đơn hàng</a><button
            class="btn btn-primary" type="button" onclick="window.print()">In hóa đơn</button></div>
    <article class="admin-invoice-sheet">
        <header><img src="{{ asset('images/brand/quan-89-logo.png') }}" alt="89 Beer Garden">
            <div><span>89 BEER GARDEN</span>
                <h1>HÓA ĐƠN BÁN HÀNG</h1>
                <p><x-display-code :code="$fulfillmentOrder->order_code" /></p>
            </div>
        </header>
        <section class="admin-invoice-meta">
            <div><span>Hình
                    thức</span><strong>{{ $fulfillmentOrder->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}</strong>
            </div>
            <div><span>Khách hàng</span><strong>{{ $fulfillmentOrder->customer_name }}</strong></div>
            <div><span>Thanh toán lúc</span><strong>{{ $fulfillmentOrder->paid_at->format('d/m/Y H:i') }}</strong></div>
            <div><span>Thu ngân</span><strong>{{ $fulfillmentOrder->paidByEmployee?->name }}</strong></div>
            @if ($fulfillmentOrder->fulfillment_type === 'delivery')
                <div style="grid-column:1/-1"><span>Địa chỉ giao
                        hàng</span><strong>{{ $fulfillmentOrder->delivery_address }}</strong></div>
            @endif
        </section>
        @foreach ($fulfillmentOrder->items as $item)
            <div class="admin-invoice-line">
                <div><strong>{{ $item->product_name }}</strong><span>{{ $item->quantity }} ×
                        {{ number_format($item->unit_price, 0, ',', '.') }}
                        ₫{{ $item->note ? ' · ' . $item->note : '' }}</span></div>
                <strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong>
            </div>
        @endforeach
        <dl class="admin-invoice-totals">
            <div>
                <dt>Tạm tính</dt>
                <dd>{{ number_format($fulfillmentOrder->subtotal, 0, ',', '.') }} ₫</dd>
            </div>
            <div>
                <dt>Giảm giá {{ $fulfillmentOrder->voucher ? '(' . $fulfillmentOrder->voucher->code . ')' : '' }}</dt>
                <dd>− {{ number_format($fulfillmentOrder->discount_amount, 0, ',', '.') }} ₫</dd>
            </div>
            @if ($fulfillmentOrder->fulfillment_type === 'delivery')
                <div>
                    <dt>Phí giao hàng</dt>
                    <dd>{{ number_format($fulfillmentOrder->shipping_fee, 0, ',', '.') }} ₫</dd>
                </div>
            @endif
            <div class="total">
                <dt>Tổng thanh toán</dt>
                <dd>{{ number_format($fulfillmentOrder->total_amount, 0, ',', '.') }} ₫</dd>
            </div>
            @if ($fulfillmentOrder->payment_method === 'cash')
                <div>
                    <dt>Tiền khách đưa</dt>
                    <dd>{{ number_format($fulfillmentOrder->received_amount, 0, ',', '.') }} ₫</dd>
                </div>
                <div>
                    <dt>Tiền trả lại</dt>
                    <dd>{{ number_format($fulfillmentOrder->change_amount, 0, ',', '.') }} ₫</dd>
                </div>
            @endif
        </dl>
        <footer>
            <p>{{ $fulfillmentOrder->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản ngân hàng' }}</p><strong>Cảm ơn
                quý khách và hẹn gặp lại!</strong>
        </footer>
    </article>
@endsection
