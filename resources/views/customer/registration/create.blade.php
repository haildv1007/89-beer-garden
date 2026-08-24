@extends('layouts.customer')
@section('title', __('customer.registration.title').' — '.__('app.name'))
@section('content')
    <div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6"><div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h3 mb-4">{{ __('customer.registration.title') }}</h1>
        <form method="post" action="{{ route('customer.registration.store') }}" novalidate>@csrf
            <div class="mb-3"><label class="form-label" for="name">{{ __('customer.fields.name') }}</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="email">{{ __('customer.fields.email') }}</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="phone">{{ __('customer.fields.phone') }}</label><input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="password">{{ __('customer.fields.password') }}</label><input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" required autocomplete="new-password"><div class="form-text">{{ __('customer.registration.password_help') }}</div>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label class="form-label" for="password_confirmation">{{ __('customer.fields.password_confirmation') }}</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <button class="btn btn-primary w-100">{{ __('customer.registration.submit') }}</button>
        </form>
    </div></div></div></div>
@endsection
