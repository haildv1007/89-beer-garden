@extends('layouts.customer')
@section('title', __('delivery_checkout.success'))
@section('content')
    <section class="flow-confirmation" aria-labelledby="delivery-confirmation-title">
        <div class="flow-confirmation__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="img">
                <path d="m6.5 12.5 3.4 3.4 7.6-8" />
            </svg>
        </div>

        <span class="flow-confirmation__eyebrow">{{ __('delivery_checkout.success_eyebrow') }}</span>

        <div class="flow-confirmation__copy">
            <h1 id="delivery-confirmation-title">{{ __('delivery_checkout.success') }}</h1>
            <p>
                {{ $orderCode ? __('delivery_checkout.success_help') : __('delivery_checkout.no_confirmation') }}
            </p>
        </div>

        @if ($orderCode)
            <div class="flow-confirmation__code">
                <span>{{ __('delivery_checkout.order_code') }}</span>
                <strong title="{{ $orderCode }}">{{ \App\Support\DisplayCode::short($orderCode) }}</strong>
            </div>
        @endif

        <a class="btn btn-primary flow-confirmation__action" href="{{ route('customer.menu.index') }}">
            {{ __('app.home.view_menu') }}
        </a>
    </section>
@endsection
