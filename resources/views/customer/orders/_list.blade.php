<div class="history-list">
    @forelse($sessions as $session)
        @php
            $validItems = $session->orders->flatMap->items->where(
                'status',
                '!=',
                \App\Enums\OrderItemStatus::Cancelled,
            );
        @endphp
        <article class="history-row">
            <div class="history-row-date">
                <strong>{{ $session->started_at->format('d/m/Y') }}</strong><span>{{ $session->started_at->format('H:i') }}</span>
            </div>
            <div class="history-row-main">
                <div>
                    <h2>{{ $session->table?->name ?: 'Tại quán' }}</h2><small title="{{ $session->session_code }}">Mã
                        tham chiếu #{{ \App\Support\DisplayCode::short($session->session_code) }}</small>
                </div>
                @php
                    $statusClass =
                        $session->status === \App\Enums\DiningSessionStatus::Active
                            ? 'text-bg-warning'
                            : 'text-bg-dark';
                @endphp
                <span class="status-badge {{ $statusClass }}">
                    {{ __('order_history.status.' . $session->status->value) }}
                </span>
            </div>
            <div class="history-row-meta"><span><strong>{{ $validItems->sum('quantity') }}</strong>
                    món</span><span><strong>{{ $session->orders->count() }}</strong> lượt gọi</span><span
                    class="history-row-total">{{ $session->bill ? number_format($session->bill->total_amount) . ' đ' : 'Chưa có hóa đơn' }}</span>
            </div>
            @unless ($adminView ?? false)
                <a class="history-row-link"
                    href="{{ route('customer.orders.history.show', $session) }}">{{ __('order_history.detail') }} <span
                        aria-hidden="true">›</span></a>
            @endunless
        </article>
    @empty<div class="empty-state"><span class="empty-state-mark" aria-hidden="true">◇</span>
            <p>{{ __('order_history.empty') }}</p>
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $sessions->links() }}</div>
