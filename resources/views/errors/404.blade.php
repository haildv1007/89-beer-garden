@extends('layouts.customer')
@section('title', __('errors.not_found_title'))
@section('content')
    <div class="text-center py-5">
        <div class="display-1 fw-bold">404</div>
        <h1 class="h2">{{ __('errors.not_found_title') }}</h1>
        <p class="text-secondary">{{ __('errors.not_found_hint') }}</p><a class="btn btn-primary"
            href="{{ route('customer.menu.index') }}">{{ __('app.back_to_menu') }}</a>
    </div>
@endsection
