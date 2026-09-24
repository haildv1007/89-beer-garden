@extends('layouts.admin')
@section('title', $table->name)
@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1>{{ $table->name }}</h1>
            <div class="text-muted">{{ $table->code }}</div>
        </div><a class="btn btn-primary" href="{{ route('admin.restaurant-tables.edit', $table) }}">{{ __('app.edit') }}</a>
    </div>
    <dl class="row">
        <dt class="col-sm-3">{{ __('table.fields.capacity') }}</dt>
        <dd class="col-sm-9">{{ $table->capacity }}</dd>
        <dt class="col-sm-3">{{ __('table.fields.location') }}</dt>
        <dd class="col-sm-9">{{ $table->location ?: '—' }}</dd>
        <dt class="col-sm-3">{{ __('table.fields.runtime_status') }}</dt>
        <dd class="col-sm-9">@include('partials.table-status-badge', ['status' => $table->runtime_status])</dd>
        <dt class="col-sm-3">{{ __('table.fields.config_status') }}</dt>
        <dd class="col-sm-9">{{ $table->is_active ? __('table.active') : __('table.inactive') }}</dd>
        <dt class="col-sm-3">{{ __('table.active_session') }}</dt>
        <dd class="col-sm-9">
            <x-display-code :code="$table->activeDiningSession?->session_code" />
        </dd>
        <dt class="col-sm-3">{{ __('table.history') }}</dt>
        <dd class="col-sm-9">
            {{ trans_choice('table.history_counts', $table->reservations_count + $table->dining_sessions_count, [
                'reservations' => $table->reservations_count,
                'sessions' => $table->dining_sessions_count,
            ]) }}
        </dd>
    </dl>
    <form method="post" action="{{ route('admin.restaurant-tables.destroy', $table) }}"
        onsubmit="return confirm(@js(__('table.admin.confirm_delete'))) ">@csrf @method('delete')<button
            class="btn btn-outline-danger">{{ __('app.delete') }}</button></form>
@endsection
