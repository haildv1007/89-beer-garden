@extends('layouts.customer')
@section('title', __('pickup_checkout.success'))
@section('content')
    <div class="context-card">
        <div class="context-icon" aria-hidden="true">✓</div><span
            class="eyebrow">{{ __('pickup_checkout.success_eyebrow') }}</span>
        <h1 class="page-title">{{ __('pickup_checkout.success') }}</h1>
        @if ($orderCode)
            <p>{{ __('pickup_checkout.success_code', ['code' => \App\Support\DisplayCode::short($orderCode)]) }}</p>
        <p>{{ __('pickup_checkout.success_help') }}</p>@else<p>{{ __('pickup_checkout.no_confirmation') }}</p>
        @endif
        <a class="btn btn-primary" href="{{ route('customer.menu.index') }}">{{ __('app.home.view_menu') }}</a>
    </div>
@endsection
