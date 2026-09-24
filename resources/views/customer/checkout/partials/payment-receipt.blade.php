<section class="order-success__receipt" aria-labelledby="payment-receipt-title">
    <header>
        <div>
            <span class="order-success__paid-status">{{ __('billing.statuses.paid') }}</span>
            <h2 id="payment-receipt-title">{{ __('billing.payment_summary') }}</h2>
        </div>
        <strong>{{ number_format($order->total_amount, 0, ',', '.') }} ₫</strong>
    </header>

    <dl class="order-success__payment-meta">
        <div><dt>{{ __('billing.method') }}</dt><dd>{{ __('billing.methods.bank_transfer') }}</dd></div>
        <div><dt>{{ __('billing.paid_at') }}</dt><dd>{{ $order->paid_at?->format('H:i d/m/Y') }}</dd></div>
    </dl>

    <div class="order-success__items">
        @foreach ($order->items as $item)
            <div>
                <span>{{ $item->quantity }} × {{ $item->product_name }}@if($item->variant_name) — {{ $item->variant_name }}@endif</span>
                <strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong>
            </div>
        @endforeach
    </div>

    <dl class="order-success__totals">
        <div><dt>{{ __('billing.subtotal') }}</dt><dd>{{ number_format($order->subtotal, 0, ',', '.') }} ₫</dd></div>
        @if ($order->discount_amount > 0)
            <div><dt>{{ __('billing.discount') }}</dt><dd>−{{ number_format($order->discount_amount, 0, ',', '.') }} ₫</dd></div>
        @endif
        @if ($order->shipping_fee > 0)
            <div><dt>{{ __('delivery_checkout.shipping_fee') }}</dt><dd>{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</dd></div>
        @endif
        <div class="is-total"><dt>{{ __('billing.total') }}</dt><dd>{{ number_format($order->total_amount, 0, ',', '.') }} ₫</dd></div>
    </dl>
</section>
