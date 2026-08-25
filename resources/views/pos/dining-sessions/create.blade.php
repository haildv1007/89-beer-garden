@extends('layouts.pos')
@section('title', __('dining_session.open_walk_in'))
@section('content')
    <h1>{{ __('dining_session.open_walk_in') }}</h1><p>{{ $restaurantTable->code }} — {{ $restaurantTable->name }} · {{ __('table.capacity_people', ['count' => $restaurantTable->capacity]) }}</p>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('pos.dining-sessions.store', $restaurantTable) }}">@csrf
        <div class="mb-3"><label class="form-label" for="guest_count">{{ __('dining_session.fields.guests') }}</label><input class="form-control" id="guest_count" name="guest_count" type="number" min="1" max="{{ $restaurantTable->capacity }}" value="{{ old('guest_count') }}" required></div>
        <div class="mb-3"><label class="form-label" for="customer_id">{{ __('dining_session.fields.customer') }}</label><select class="form-select" id="customer_id" name="customer_id"><option value="">{{ __('dining_session.anonymous') }}</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}{{ $customer->phone ? ' — '.$customer->phone : '' }}</option>@endforeach</select></div>
        <div class="mb-3"><label class="form-label" for="note">{{ __('dining_session.fields.note') }}</label><textarea class="form-control" id="note" name="note">{{ old('note') }}</textarea></div>
        <button class="btn btn-primary">{{ __('dining_session.open') }}</button>
    </form>
@endsection
