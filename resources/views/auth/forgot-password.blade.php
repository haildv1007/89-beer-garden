@extends('layouts.customer')
@section('title', __('auth.password_reset.request_title') . ' - ' . __('app.name'))
@section('content')
    <div class="form-card auth-card">
        <div class="row g-0">
            <div class="col-lg-5">
                <aside class="form-aside auth-aside auth-aside-login d-flex flex-column justify-content-end">
                    <span class="eyebrow">{{ __('app.name') }}</span>
                    <h1>{{ __('auth.password_reset.request_heading') }}</h1>
                    <p class="mb-0">{{ __('auth.password_reset.request_copy') }}</p>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="form-body auth-form-body">
                    <h2 class="h3 fw-bold mb-1">{{ __('auth.password_reset.request_title') }}</h2>
                    <p class="text-secondary mb-4">{{ __('auth.password_reset.request_instructions') }}</p>
                    <form class="js-submit-once" method="post" action="{{ route('password.email') }}" novalidate>
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="form-label">{{ __('auth.email') }} <span aria-hidden="true">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                autocomplete="email" autofocus required aria-describedby="email-error"
                                class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div id="email-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('auth.password_reset.send_link') }}</button>
                    </form>
                    <p class="text-center mt-4 mb-0"><a class="auth-secondary-link"
                            href="{{ route('login') }}">{{ __('auth.password_reset.back_to_login') }}</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
