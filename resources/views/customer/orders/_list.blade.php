@forelse($sessions as $session)
    @php($validItems = $session->orders->flatMap->items->where('status', '!=', \App\Enums\OrderItemStatus::Cancelled))
    <article class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between"><h2 class="h5">{{ $session->session_code }}</h2><span>{{ __('order_history.status.'.$session->status->value) }}</span></div>
        <p>{{ __('order_history.table') }}: {{ $session->table?->code }} — {{ $session->table?->name }} · {{ __('order_history.started') }}: {{ $session->started_at }} · {{ __('order_history.ended') }}: {{ $session->ended_at ?: '—' }}</p>
        <p>{{ __('order_history.order_count') }}: {{ $session->orders_count }} · {{ __('order_history.item_count') }}: {{ $validItems->sum('quantity') }} · {{ __('order_history.latest') }}: {{ $session->orders->max('ordered_at') ?: $session->started_at }}</p>
        @if(isset($adminHistory))<p>{{ $session->orders->pluck('order_code')->join(' · ') }}</p>@endif
        @if($session->bill?->status === \App\Enums\BillStatus::Paid && $session->bill->successfulPayment)<p>{{ __('order_history.paid') }}: {{ number_format($session->bill->successfulPayment->amount) }} đ</p>@else<p>{{ __('order_history.not_paid') }}</p>@endif
        @unless(isset($adminHistory))<a href="{{ route('customer.orders.history.show', $session) }}">{{ __('order_history.detail') }}</a>@endunless
    </div></article>
@empty<div class="alert alert-info">{{ __('order_history.empty') }}</div>@endforelse
{{ $sessions->links() }}
