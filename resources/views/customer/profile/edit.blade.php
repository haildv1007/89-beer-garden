@extends('layouts.customer')
@section('title', __('customer.profile.edit').' — '.__('app.name'))
@section('content')
    <div class="row justify-content-center"><div class="col-12 col-md-8"><h1>{{ __('customer.profile.edit') }}</h1><form method="post" action="{{ route('customer.profile.update', $customer) }}">@csrf @method('put')
        <div class="mb-3"><label class="form-label" for="name">{{ __('customer.fields.name') }}</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $customer->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label" for="email">{{ __('customer.fields.email') }}</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label" for="phone">{{ __('customer.fields.phone') }}</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button class="btn btn-primary">{{ __('app.save') }}</button> <a class="btn btn-outline-secondary" href="{{ route('customer.profile.show', $customer) }}">{{ __('customer.cancel') }}</a>
    </form></div></div>
@endsection
