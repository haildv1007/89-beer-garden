@extends('layouts.customer')
@section('title', __('errors.forbidden_title'))
@section('content')
    <div class="text-center py-5">
        <div class="display-1 fw-bold">403</div>
        <h1 class="h2">{{ __('errors.forbidden_title') }}</h1>
        <p class="text-secondary">{{ __('errors.dining_link_hint') }}</p><a class="btn btn-primary"
            href="{{ route('customer.menu.index') }}">{{ __('app.back_to_menu') }}</a>
    </div>
@endsection
