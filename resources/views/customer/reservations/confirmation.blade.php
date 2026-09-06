@extends('layouts.customer')
@section('title', __('reservation.customer.confirmation_title') . ' — ' . __('app.name'))
@section('content')
    <section class="flow-confirmation" aria-labelledby="reservation-confirmation-title">
        <div class="flow-confirmation__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="img">
                <path d="m6.5 12.5 3.4 3.4 7.6-8" />
            </svg>
        </div>

        <span class="status-badge text-bg-warning">
            {{ __('reservation.statuses.pending') }}
        </span>

        <div class="flow-confirmation__copy">
            <h1 id="reservation-confirmation-title">{{ __('reservation.customer.confirmation_title') }}</h1>
            <p>{{ __('reservation.customer.confirmation_message') }}</p>
        </div>

        <div class="flow-confirmation__code">
            <span>{{ __('reservation.fields.code') }}</span>
            <strong title="{{ $reservationCode }}">{{ \App\Support\DisplayCode::short($reservationCode) }}</strong>
        </div>

        <a class="btn btn-primary flow-confirmation__action" href="{{ route('customer.menu.index') }}">
            {{ __('app.home.view_menu') }}
        </a>
    </section>
@endsection
