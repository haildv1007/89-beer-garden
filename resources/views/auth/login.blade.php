@extends('layouts.customer')

@section('title', __('auth.login').' — '.__('app.name'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">{{ __('auth.login') }}</h1>

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('auth.email') }}</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                autofocus
                                required
                                class="form-control @error('email') is-invalid @enderror"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">{{ __('auth.labels.password') }}</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="form-control @error('password') is-invalid @enderror"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('auth.login') }}
                        </button>
                    </form>
                    <p class="text-center mt-3 mb-0"><a href="{{ route('customer.registration.create') }}">{{ __('customer.registration.login_prompt') }}</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
