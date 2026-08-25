@extends('layouts.pos')
@section('title', __('table.pos.map'))
@section('content')
    <h1>{{ __('table.pos.map') }}</h1>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form class="row g-2 mb-4" method="get">
        <div class="col-lg-3"><input class="form-control form-control-lg" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('table.search_placeholder') }}"></div>
        <div class="col-lg-2"><select class="form-select form-select-lg" name="status"><option value="">{{ __('table.all_statuses') }}</option>@foreach (\App\Enums\RestaurantTableStatus::cases() as $option)<option value="{{ $option->value }}" @selected(($filters['status'] ?? '') === $option->value)>{{ __('table.statuses.'.$option->value) }}</option>@endforeach</select></div>
        <div class="col-lg-2"><select class="form-select form-select-lg" name="location"><option value="">{{ __('table.all_locations') }}</option>@foreach ($locations as $location)<option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>@endforeach</select></div>
        <div class="col-lg-2"><select class="form-select form-select-lg" name="active"><option value="all">{{ __('table.all_config_states') }}</option><option value="1" @selected(($filters['active'] ?? 'all') === '1')>{{ __('table.active') }}</option><option value="0" @selected(($filters['active'] ?? 'all') === '0')>{{ __('table.inactive') }}</option></select></div>
        <div class="col-lg-2"><input class="form-control form-control-lg" name="party_size" type="number" min="1" value="{{ $filters['party_size'] ?? '' }}" placeholder="{{ __('table.party_size') }}"></div>
        <div class="col-lg-1"><button class="btn btn-primary btn-lg w-100">{{ __('app.search') }}</button></div>
    </form>
    @if ($partySize !== null)<div class="alert alert-info">{{ __('table.pos.suggestion_for', ['count' => $partySize]) }}</div>@endif
    @if ($tables->isEmpty())<div class="alert alert-secondary">{{ $partySize === null ? __('table.empty') : __('table.pos.no_suggestion') }}</div>@else
        <div class="row g-3">@foreach ($tables as $table)
            <div class="col-sm-6 col-lg-4 col-xl-3"><article class="card h-100 {{ $table->is_active ? '' : 'bg-light text-muted' }}"><div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between gap-2"><h2 class="h4">{{ $table->name }}</h2>@include('partials.table-status-badge', ['status' => $table->runtime_status])</div>
                <div class="fw-semibold">{{ $table->code }}</div><div>{{ __('table.capacity_people', ['count' => $table->capacity]) }}</div><div>{{ $table->location ?: __('table.no_location') }}</div>
                <div class="mt-2"><span class="badge {{ $table->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $table->is_active ? __('table.active') : __('table.inactive') }}</span></div>
                @if ($table->activeDiningSession)<div class="mt-3 alert alert-warning py-2 mb-0">@can('dining-session.view')<a href="{{ route('pos.dining-sessions.show', $table->activeDiningSession) }}">{{ __('table.pos.active_session_context', ['code' => $table->activeDiningSession->session_code, 'count' => $table->activeDiningSession->guest_count]) }}</a>@else{{ __('table.pos.active_session_context', ['code' => $table->activeDiningSession->session_code, 'count' => $table->activeDiningSession->guest_count]) }}@endcan</div>@endif
                @if ($table->is_active && $table->runtime_status === \App\Enums\RestaurantTableStatus::Available && auth()->user()->can('dining-session.open') && auth()->user()->can('table.operate'))<a class="btn btn-primary mt-auto" href="{{ route('pos.dining-sessions.create', $table) }}">{{ __('dining_session.open_walk_in') }}</a>@endif
                @if ($table->runtime_status === \App\Enums\RestaurantTableStatus::Cleaning && auth()->user()->can('table.operate'))<form class="mt-auto pt-3" method="post" action="{{ route('pos.tables.mark-available', $table) }}">@csrf @method('patch')<button class="btn btn-success btn-lg w-100">{{ __('table.pos.mark_available') }}</button></form>@endif
            </div></article></div>
        @endforeach</div>
        <div class="mt-4">{{ $tables->links() }}</div>
    @endif
@endsection
