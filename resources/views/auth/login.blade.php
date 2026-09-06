@extends('layouts.customer')
@section('title', __('auth.login') . ' — ' . __('app.name'))
@section('content')
    <div class="form-card auth-card">
        <div class="row g-0">
            <div class="col-lg-5">
                <aside class="form-aside auth-aside auth-aside-login d-flex flex-column justify-content-end"><span
                        class="eyebrow">{{ __('app.name') }}</span>
                    <h1>{{ __('customer_ui.auth_welcome') }}</h1>
                    <p class="mb-0">{{ __('customer_ui.auth_copy') }}</p>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="form-body auth-form-body">
                    <h2 class="h3 fw-bold mb-1">{{ __('auth.login') }}</h2>
                    <p class="text-secondary mb-4">Đăng nhập để quản lý đặt bàn và đơn hàng của bạn.</p>
                    @if ($googleLoginEnabled)
                        <a class="btn auth-google-btn w-100" href="{{ route('auth.google.redirect') }}"><svg
                                aria-hidden="true" viewBox="0 0 24 24">
                                <path fill="#4285F4"
                                    d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.9h5.4a4.6 4.6 0 0 1-2 3v2.5h3.2c1.9-1.8 3-4.4 3-7.4Z" />
                                <path fill="#34A853"
                                    d="M12 22c2.7 0 5-.9 6.6-2.4l-3.2-2.5c-.9.6-2 .9-3.4.9-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22Z" />
                                <path fill="#FBBC05"
                                    d="M6.4 13.9A6 6 0 0 1 6.1 12c0-.7.1-1.3.3-1.9V7.5H3.1A10 10 0 0 0 2 12c0 1.6.4 3.1 1.1 4.5l3.3-2.6Z" />
                                <path fill="#EA4335"
                                    d="M12 6c1.5 0 2.8.5 3.8 1.5l2.9-2.8A9.7 9.7 0 0 0 12 2a10 10 0 0 0-8.9 5.5l3.3 2.6C7.2 7.8 9.4 6 12 6Z" />
                            </svg>Tiếp tục với Google</a>
                        <div class="auth-divider"><span>hoặc</span></div>
                    @endif
                    <form class="js-submit-once" method="post" action="{{ route('login') }}" novalidate>@csrf
                        <div class="mb-3"><label for="login" class="form-label">Số điện thoại hoặc email <span
                                    aria-hidden="true">*</span></label><input id="login" name="login"
                                value="{{ old('login') }}" autocomplete="username" autofocus required
                                aria-describedby="login-error" class="form-control @error('login') is-invalid @enderror"
                                placeholder="Nhập số điện thoại hoặc email">
                            @error('login')
                                <div id="login-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4"><label for="password" class="form-label">{{ __('auth.labels.password') }} <span
                                    aria-hidden="true">*</span></label><input id="password" name="password" type="password"
                                autocomplete="current-password" required aria-describedby="password-error"
                                class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div id="password-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('auth.login') }}</button>
                    </form>
                    <p class="text-center mt-4 mb-0"><a
                            href="{{ route('customer.registration.create') }}">{{ __('customer.registration.login_prompt') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
