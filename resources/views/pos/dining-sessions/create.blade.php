@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', __('dining_session.open_walk_in'))
@section('content')
    <div class="operation-form-page">
        <a class="operation-back-link"
            href="{{ route($adminContext ?? false ? 'admin.tables.index' : 'pos.tables.index') }}">← Sơ đồ bàn</a>
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
            <div class="mb-3"><label class="form-label"
                    for="customer_id">{{ __('dining_session.fields.customer') }}</label><select class="form-select"
                    id="customer_id" name="customer_id">
                    <option value="">{{ __('dining_session.anonymous') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->name }}{{ $customer->phone ? ' — ' . $customer->phone : '' }}</option>
                    @endforeach
                </select></div>
            <div class="mb-3"><label class="form-label" for="note">{{ __('dining_session.fields.note') }}</label>
                <textarea class="form-control" id="note" name="note">{{ old('note') }}</textarea>
            </div>
            <div class="operation-form-actions"><a class="btn btn-outline-secondary"
                    href="{{ route($adminContext ?? false ? 'admin.tables.index' : 'pos.tables.index') }}">Hủy</a><button
                    class="btn btn-primary">{{ __('dining_session.open') }}</button></div>
        </form>
    </div>
@endsection
