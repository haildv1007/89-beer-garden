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
    <div><label class="form-label" for="history-status">{{ __('order_history.status_label') }}</label><select id="history-status"
            class="form-select" name="status">
            <option value="">{{ __('order_history.all_statuses') }}</option>
            @foreach (\App\Enums\DiningSessionStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                    {{ __('order_history.status.' . $status->value) }}</option>
            @endforeach
        </select></div>
    <button class="btn btn-primary">{{ __('order_history.filter') }}</button>
    @if (collect($filters)->filter()->isNotEmpty())
        <a href="{{ route('customer.orders.history') }}">{{ __('order_history.clear_filters') }}</a>
    @endif
</form>
