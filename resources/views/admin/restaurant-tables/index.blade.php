@extends('layouts.admin')
@section('title', __('table.admin.title'))
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('table.admin.title') }}</h1>
        <a class="btn btn-primary" href="{{ route('admin.restaurant-tables.create') }}">{{ __('table.admin.create') }}</a>
    </div>
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-4"><input class="form-control" name="q" value="{{ $search }}" placeholder="{{ __('table.search_placeholder') }}"></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">{{ __('table.all_statuses') }}</option>@foreach (\App\Enums\RestaurantTableStatus::cases() as $option)<option value="{{ $option->value }}" @selected($status === $option->value)>{{ __('table.statuses.'.$option->value) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="active"><option value="">{{ __('table.all_config_states') }}</option><option value="1" @selected($active === '1')>{{ __('table.active') }}</option><option value="0" @selected($active === '0')>{{ __('table.inactive') }}</option></select></div>
        <div class="col-md-2"><input class="form-control" type="number" min="1" name="min_capacity" value="{{ $minCapacity }}" placeholder="{{ __('table.minimum_capacity') }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.search') }}</button></div>
    </form>
    @if ($tables->isEmpty())
        <div class="alert alert-info">{{ __('table.empty') }}</div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>{{ __('table.fields.code') }}</th><th>{{ __('table.fields.name') }}</th><th>{{ __('table.fields.capacity') }}</th><th>{{ __('table.fields.location') }}</th><th>{{ __('table.fields.runtime_status') }}</th><th>{{ __('table.fields.config_status') }}</th><th></th></tr></thead>
            <tbody>@foreach ($tables as $table)<tr class="{{ $table->is_active ? '' : 'table-secondary' }}"><td>{{ $table->code }}</td><td>{{ $table->name }}</td><td>{{ $table->capacity }}</td><td>{{ $table->location ?: '—' }}</td><td>@include('partials.table-status-badge', ['status' => $table->runtime_status])</td><td><span class="badge {{ $table->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $table->is_active ? __('table.active') : __('table.inactive') }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.restaurant-tables.show', $table) }}">{{ __('app.view_details') }}</a></td></tr>@endforeach</tbody>
        </table></div>
        {{ $tables->links() }}
    @endif
@endsection
