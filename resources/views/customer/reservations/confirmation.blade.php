@extends('layouts.customer')
@section('title', __('reservation.customer.confirmation_title') . ' - ' . __('app.name'))
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

        <section class="flow-confirmation__details" aria-labelledby="reservation-details-title">
            <h2 id="reservation-details-title">{{ __('reservation.customer.request_details') }}</h2>
            <dl>
                <div><dt>{{ __('reservation.fields.name') }}</dt><dd>{{ $reservationDetails['name'] }}</dd></div>
                <div><dt>{{ __('reservation.fields.phone') }}</dt><dd>{{ $reservationDetails['phone'] }}</dd></div>
                <div><dt>{{ __('reservation.fields.date') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($reservationDetails['reservation_date'])->format('d/m/Y') }}</dd></div>
                <div><dt>{{ __('reservation.fields.time') }}</dt><dd>{{ substr($reservationDetails['reservation_time'], 0, 5) }}</dd></div>
                <div><dt>{{ __('reservation.fields.party_size') }}</dt><dd>{{ __('reservation.people', ['count' => $reservationDetails['party_size']]) }}</dd></div>
                @if (filled($reservationDetails['note']))
                    <div class="flow-confirmation__note"><dt>{{ __('reservation.fields.note') }}</dt><dd>{{ $reservationDetails['note'] }}</dd></div>
                @endif
            </dl>
        </section>

        @if ($reservationPreorder)
            <section class="reservation-preorder reservation-preorder--confirmation" aria-labelledby="reservation-preorder-title">
                <h2 id="reservation-preorder-title">{{ __('customer_order.preorder_summary') }}</h2>
                @foreach ($reservationPreorder['items'] as $item)
                    <div>
                        <span>{{ $item['name'] }} × {{ $item['quantity'] }}
                            @if (filled($item['note']))
                                <small class="reservation-preorder__item-note">{{ __('customer_order.item_note') }}: {{ $item['note'] }}</small>
                            @endif
                        </span>
                        <strong>{{ number_format($item['line_total'], 0, ',', '.') }} ₫</strong>
                    </div>
                @endforeach
                <div class="reservation-preorder-total">
                    <span>{{ __('checkout.total') }}</span>
                    <strong>{{ number_format($reservationPreorder['total'], 0, ',', '.') }} ₫</strong>
                </div>
            </section>
        @endif

        <a class="btn btn-primary flow-confirmation__action" href="{{ route('customer.menu.index') }}">
            {{ __('app.home.view_menu') }}
        </a>
    </section>
@endsection
