@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', __('dining_session.open_walk_in'))
@section('content')
    @php($tableRoutePrefix = $adminContext ?? false ? 'admin' : 'pos')
    <div class="operation-form-page">
        <header class="page-heading">
            <div>
                <h1>{{ __('dining_session.open_walk_in') }}</h1>
                <p>{{ $restaurantTable->code }} · {{ $restaurantTable->name }} ·
                    {{ __('table.capacity_people', ['count' => $restaurantTable->capacity]) }}</p>
            </div>
        </header>
        <form class="operation-form-card" method="post"
            action="{{ route($adminContext ?? false ? 'admin.dining-sessions.store' : 'pos.dining-sessions.store', $restaurantTable) }}">
            @csrf
            <div class="mb-3"><label class="form-label"
                    for="guest_count">{{ __('dining_session.fields.guests') }}</label><input class="form-control"
                    id="guest_count" name="guest_count" type="number" min="1" max="{{ $restaurantTable->capacity }}"
                    value="{{ old('guest_count') }}" required></div>
            <div class="mb-3">@include('pos.dining-sessions._customer-picker')</div>
            <div class="mb-3"><label class="form-label" for="note">{{ __('dining_session.fields.note') }}</label>
                <textarea class="form-control" id="note" name="note">{{ old('note') }}</textarea>
            </div>
            <div class="operation-form-actions"><a class="btn btn-outline-secondary"
                    href="{{ route($adminContext ?? false ? 'admin.tables.index' : 'pos.tables.index') }}">Hủy</a><button
                    class="btn btn-primary">{{ __('dining_session.open') }}</button></div>
        </form>
    </div>
@endsection
