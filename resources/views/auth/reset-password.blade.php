@extends('layouts.customer')
@section('title', __('auth.password_reset.reset_title') . ' - ' . __('app.name'))
@section('content')
    <div class="form-card auth-card">
        <div class="row g-0">
            <div class="col-lg-5">
                <aside class="form-aside auth-aside auth-aside-login d-flex flex-column justify-content-end">
                    <span class="eyebrow">{{ __('app.name') }}</span>
                    <h1>{{ __('auth.password_reset.reset_heading') }}</h1>
                    <p class="mb-0">{{ __('auth.password_reset.reset_copy') }}</p>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="form-body auth-form-body">
                    <h2 class="h3 fw-bold mb-1">{{ __('auth.password_reset.reset_title') }}</h2>
                    <p class="text-secondary mb-4">{{ __('auth.password_reset.reset_instructions') }}</p>
                    <form class="js-submit-once" method="post" action="{{ route('password.update') }}" novalidate>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('auth.email') }} <span aria-hidden="true">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email', $email) }}"
                                autocomplete="email" required aria-describedby="email-error"
                                class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div id="email-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('auth.password_reset.new_password') }} <span aria-hidden="true">*</span></label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                aria-describedby="password-help password-error"
                                class="form-control @error('password') is-invalid @enderror">
                            <div id="password-help" class="form-text">{{ __('auth.password_reset.password_help') }}</div>
                            @error('password')
                                <div id="password-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">{{ __('auth.password_reset.confirm_password') }} <span aria-hidden="true">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                autocomplete="new-password" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('auth.password_reset.reset_button') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
