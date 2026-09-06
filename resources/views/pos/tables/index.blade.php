@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', __('table.pos.map'))
@section('content')
    @php
        $tableRoutePrefix = $adminContext ?? false ? 'admin' : 'pos';
    @endphp
    <div class="ops-page ops-page--table-map">
        <header class="page-heading ops-page-heading">
            <div>
                <h1>{{ __('table.pos.map') }}</h1>
                <p>{{ __('table.pos.map_lead') }}</p>
            </div>
            @if ($adminContext ?? false)
                <a class="btn btn-outline-primary" href="{{ route('admin.restaurant-tables.index') }}">Quản lý cấu hình
                    bàn</a>
            @endif
            @if ($partySize !== null)
                <span
                    class="status-badge text-bg-warning">{{ __('table.pos.suggestion_for', ['count' => $partySize]) }}</span>
            @endif
        </header>
        <form class="filter-bar ops-filter" method="get" aria-label="{{ __('table.pos.map_filters') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3"><label class="form-label" for="q">{{ __('app.search') }}</label><input
                        class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="{{ __('table.search_placeholder') }}"></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label"
                        for="status">{{ __('table.fields.runtime_status') }}</label><select class="form-select"
                        id="status" name="status">
                        <option value="">{{ __('table.all_statuses') }}</option>
                        @foreach (\App\Enums\RestaurantTableStatus::cases() as $option)
                            <option value="{{ $option->value }}" @selected(($filters['status'] ?? '') === $option->value)>
                                {{ __('table.statuses.' . $option->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2"><label class="form-label"
                        for="location">{{ __('table.fields.location') }}</label><select class="form-select" id="location"
                        name="location">
                        <option value="">{{ __('table.all_locations') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2"><label class="form-label"
                        for="active">{{ __('table.fields.config_status') }}</label><select class="form-select"
                        id="active" name="active">
                        <option value="all">{{ __('table.all_config_states') }}</option>
                        <option value="1" @selected(($filters['active'] ?? 'all') === '1')>{{ __('table.active') }}</option>
                        <option value="0" @selected(($filters['active'] ?? 'all') === '0')>{{ __('table.inactive') }}</option>
                    </select></div>
                <div class="col-sm-6 col-lg-1"><label class="form-label"
                        for="party_size">{{ __('table.party_size') }}</label><input class="form-control" id="party_size"
                        name="party_size" type="number" min="1" value="{{ $filters['party_size'] ?? '' }}"></div>
                <div class="col-lg-2 d-flex gap-2"><button
                        class="btn btn-primary flex-grow-1">{{ __('app.search') }}</button><a
                        class="btn btn-outline-secondary" href="{{ route($tableRoutePrefix . '.tables.index') }}"
                        aria-label="{{ __('app.clear_filters') }}">×</a></div>
            </div>
        </form>
        @if ($tables->isEmpty())
            <div class="empty-state">{{ $partySize === null ? __('table.empty') : __('table.pos.no_suggestion') }}</div>
        @else
            <div class="table-map ops-card-grid">
                @foreach ($tables as $table)
                    <article
                        class="table-card table-card--{{ $table->runtime_status->value }} {{ $table->is_active ? '' : 'table-card--inactive' }}">
                        <div class="table-card__head">
                            <div>
                                <h2>{{ $table->name }}</h2>
                                <div class="table-card__code">{{ $table->code }}</div>
                            </div>@include('partials.table-status-badge', ['status' => $table->runtime_status])
                        </div>
                        <div class="table-card__meta">
                            <span>{{ __('table.capacity_people', ['count' => $table->capacity]) }}</span><span>{{ $table->location ?: __('table.no_location') }}</span>
                            @unless ($table->is_active)
                                <span>{{ __('table.inactive') }}</span>
                            @endunless
                        </div>
                        @if ($table->activeDiningSession)
                            <div class="table-card__session">
                                @can('dining-session.view')
                                    <a
                                        href="{{ route($tableRoutePrefix . '.dining-sessions.show', $table->activeDiningSession) }}">
                                        {{ __('table.pos.active_session_context', [
                                            'code' => $table->activeDiningSession->session_code,
                                            'count' => $table->activeDiningSession->guest_count,
                                        ]) }}
                                    </a>
                                @else
                                    {{ __('table.pos.active_session_context', [
                                        'code' => $table->activeDiningSession->session_code,
                                        'count' => $table->activeDiningSession->guest_count,
                                    ]) }}
                                @endcan
                            </div>
                        @endif
                        <div class="table-card__action">
                            @if (
                                $table->is_active &&
                                    $table->runtime_status === \App\Enums\RestaurantTableStatus::Available &&
                                    auth()->user()->can('dining-session.open') &&
                                    auth()->user()->can('table.operate'))
                                <a class="btn btn-primary w-100"
                                    href="{{ route($tableRoutePrefix . '.dining-sessions.create', $table) }}">{{ __('dining_session.open_walk_in') }}</a>
                            @endif
                            @if ($table->runtime_status === \App\Enums\RestaurantTableStatus::Cleaning && auth()->user()->can('table.operate'))
                                <form method="post"
                                    action="{{ route($tableRoutePrefix . '.tables.mark-available', $table) }}">@csrf
                                    @method('patch')<button
                                        class="btn btn-primary w-100">{{ __('table.pos.mark_available') }}</button></form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $tables->links() }}</div>
        @endif
    </div>
@endsection
