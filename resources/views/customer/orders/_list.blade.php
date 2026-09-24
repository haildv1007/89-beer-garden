<div class="history-list">
    @forelse($transactions as $transaction)
        @php
            $paidAt = \Illuminate\Support\Carbon::parse($transaction->paid_at);
            $outside = $transaction->source_type !== 'dine_in';
            $sourceLabel = match ($transaction->source_type) {
                'delivery' => 'Giao tận nơi',
                'pickup' => 'Nhận tại quán',
                default => $transaction->place_name ?: 'Tại quán',
            };
            $detailUrl = $outside
                ? route('customer.orders.history.fulfillment', $transaction->source_id)
                : route('customer.orders.history.show', $transaction->source_id);
        @endphp
        <article class="history-row payment-history-row">
            <div class="history-row-date"><strong>{{ $paidAt->format('d/m/Y') }}</strong><span>{{ $paidAt->format('H:i') }}</span></div>
            <div class="history-row-main"><div><h2>{{ $sourceLabel }}</h2><small>{{ $outside ? $transaction->code : ($transaction->place_code ?: 'Không có mã bàn') }}</small></div>
                <span class="status-badge text-bg-success">Đã thanh toán</span></div>
            <div class="history-row-meta">
                <span><small>{{ $outside ? 'Mã đơn' : 'Mã hóa đơn' }}</small><strong><x-display-code :code="$transaction->code" /></strong></span>
                <span><small>Phương thức</small><strong>{{ __('billing.methods.' . $transaction->method) }}</strong></span>
                <span class="history-row-total">{{ number_format($transaction->total_amount, 0, ',', '.') }} ₫</span>
            </div>
            <a class="history-row-link" href="{{ $detailUrl }}">Xem hóa đơn <span aria-hidden="true">›</span></a>
        </article>
    @empty
        <div class="empty-state"><span class="empty-state-mark" aria-hidden="true">◇</span><p>Chưa có lịch sử thanh toán.</p></div>
    @endforelse
</div>
<div class="mt-4">{{ $transactions->links() }}</div>
