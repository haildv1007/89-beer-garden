<form class="history-filters" method="get">
    <div><label class="form-label" for="history-from">{{ __('order_history.from_date') }}</label><input id="history-from" class="form-control"
            type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div><label class="form-label" for="history-to">{{ __('order_history.to_date') }}</label><input id="history-to"
            class="form-control @error('to') is-invalid @enderror" type="date" name="to"
            value="{{ $filters['to'] ?? '' }}">
        @error('to')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div><label class="form-label" for="history-method">Phương thức</label><select id="history-method"
            class="form-select" name="method">
            <option value="">Tất cả phương thức</option>
            @foreach (['cash', 'bank_transfer', 'other'] as $method)
                <option value="{{ $method }}" @selected(($filters['method'] ?? '') === $method)>{{ __('billing.methods.' . $method) }}</option>
            @endforeach
        </select></div>
    <button class="btn btn-primary">{{ __('order_history.filter') }}</button>
    @if (collect($filters)->filter()->isNotEmpty())
        <a href="{{ route('customer.orders.history') }}">{{ __('order_history.clear_filters') }}</a>
    @endif
</form>
