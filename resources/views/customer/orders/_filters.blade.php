<form class="row g-2 mb-4" method="get">
    <div class="col-md-3"><input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('order_history.search') }}"></div>
    <div class="col-md-2"><input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="col-md-2"><input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="">{{ __('order_history.all_statuses') }}</option>@foreach(\App\Enums\DiningSessionStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ __('order_history.status.'.$status->value) }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('order_history.filter') }}</button></div>
</form>
@error('to')<div class="alert alert-danger">{{ $message }}</div>@enderror
