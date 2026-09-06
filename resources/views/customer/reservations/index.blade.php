@extends('layouts.customer')
@section('title', __('reservation.customer.mine') . ' — ' . __('app.name'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')
        <header class="account-heading">
            <div><span class="account-kicker">{{ __('customer_ui.account') }}</span>
                <h1>{{ __('reservation.customer.mine') }}</h1>
                <p>{{ __('customer_ui.reservation_list_copy') }}</p>
            </div><a class="btn btn-primary btn-reservation"
                href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a>
        </header>
        @if ($reservations->isEmpty())
            <div class="empty-state"><span class="empty-state-mark" aria-hidden="true">◇</span>
                <p>{{ __('reservation.customer.empty') }}</p><a class="btn btn-primary btn-reservation"
                    href="{{ route('customer.reservations.create') }}">{{ __('reservation.customer.make') }}</a>
        </div>@else<div class="reservation-list">
                @foreach ($reservations as $reservation)
                    <a class="reservation-row" href="{{ route('customer.reservations.show', $reservation) }}"><time
                            datetime="{{ $reservation->reservation_date->toDateString() }}"><strong>{{ $reservation->reservation_date->format('d') }}</strong><span>Tháng
                                {{ $reservation->reservation_date->format('m') }}</span></time>
                        <div class="reservation-row-main">
                            <h2>{{ substr($reservation->reservation_time, 0, 5) }} ·
                                {{ trans_choice('reservation.people', $reservation->party_size, ['count' => $reservation->party_size]) }}
                            </h2>
                            <p>{{ $reservation->table ? $reservation->table->name : __('reservation.customer.table_not_assigned') }}
                            </p><small title="{{ $reservation->reservation_code }}">Mã đặt bàn
                                #{{ \App\Support\DisplayCode::short($reservation->reservation_code) }}</small>
                        </div>
                        <div class="reservation-row-status">@include('partials.reservation-status-badge', ['status' => $reservation->status])<span aria-hidden="true">›</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-4">{{ $reservations->links() }}</div>
        @endif
    </div>
@endsection
