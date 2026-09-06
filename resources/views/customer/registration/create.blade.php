@extends('layouts.customer')
@section('title', __('customer.registration.title') . ' — ' . __('app.name'))
@section('content')
    <div class="form-card auth-card">
        <div class="row g-0">
            <div class="col-lg-5">
                <aside class="form-aside auth-aside auth-aside-register d-flex flex-column justify-content-end"><span
                        class="eyebrow">{{ __('customer.registration.action') }}</span>
                    <h1>{{ __('customer_ui.register_welcome') }}</h1>
                    <p class="mb-0">{{ __('customer_ui.register_copy') }}</p>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="form-body auth-form-body">
                    <h2 class="h3 fw-bold mb-4">{{ __('customer.registration.title') }}</h2>
                    @if ($googleLoginEnabled)
                        <a class="btn auth-google-btn w-100" href="{{ route('auth.google.redirect') }}">{{ __('auth.google_continue') }}</a>
                        <div class="auth-divider"><span>{{ __('auth.register_divider') }}</span></div>
                    @endif
                    <form class="js-submit-once auth-register-form" method="post"
                        action="{{ route('customer.registration.store') }}" novalidate>@csrf
                        <div class="auth-field"><label class="form-label"
                                for="name">{{ __('customer.fields.name') }}</label><input
                                class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                value="{{ old('name') }}" autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="auth-field"><label class="form-label" for="phone">{{ __('customer.fields.phone') }}
                                *</label><input class="form-control @error('phone') is-invalid @enderror" id="phone"
                                name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel"
                                placeholder="09xxxxxxxx">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="auth-field auth-field--wide"><label class="form-label"
                                for="email">{{ __('customer.fields.email') }}</label><input
                                class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                                name="email" value="{{ old('email') }}" autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="auth-field"><label class="form-label"
                                for="password">{{ __('customer.fields.password') }} *</label><input
                                class="form-control @error('password') is-invalid @enderror" id="password" type="password"
                                name="password" required autocomplete="new-password">
                            <div class="form-text">{{ __('customer.registration.password_help') }}</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="auth-field"><label class="form-label"
                                for="password_confirmation">{{ __('customer.fields.password_confirmation') }}
                                *</label><input class="form-control" id="password_confirmation" type="password"
                                name="password_confirmation" required autocomplete="new-password"></div><button
                            class="btn btn-primary auth-field--wide">{{ __('customer.registration.submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
