@extends('layouts.customer')
@section('title', isset($paymentReceipt) ? __('billing.confirmation_title') : __('pickup_checkout.success'))
@section('content')
    <section class="order-success" aria-labelledby="pickup-confirmation-title">
        <div class="order-success__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m6.5 12.5 3.4 3.4 7.6-8" /></svg>
        </div>
        <span class="order-success__eyebrow">{{ isset($paymentReceipt) ? __('billing.confirmation_eyebrow') : __('pickup_checkout.success_eyebrow') }}</span>
        <div class="order-success__copy">
            <h1 id="pickup-confirmation-title">{{ isset($paymentReceipt) ? __('billing.confirmation_title') : __('pickup_checkout.success') }}</h1>
            <p>{{ isset($paymentReceipt) ? __('billing.confirmation_help') : ($orderCode ? __('pickup_checkout.success_help') : __('pickup_checkout.no_confirmation')) }}</p>
        </div>
        @if ($orderCode)
            <div class="order-success__code">
                <span>{{ __('pickup_checkout.order_code') }}</span>
                <strong title="{{ $orderCode }}">{{ \App\Support\DisplayCode::short($orderCode) }}</strong>
            </div>
        @endif
        @isset($paymentReceipt)
            @include('customer.checkout.partials.payment-receipt', ['order' => $paymentReceipt])
        @endisset
        <a class="btn btn-primary order-success__action" href="{{ route('customer.menu.index') }}">
            {{ __('app.home.view_menu') }}
        </a>
    </section>
@endsection
