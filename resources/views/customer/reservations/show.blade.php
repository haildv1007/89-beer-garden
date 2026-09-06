@extends('layouts.customer')
@section('title', 'Chi tiết đặt bàn — ' . __('app.name'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')
        <a class="account-back" href="{{ route('customer.reservations.index') }}">← {{ __('reservation.customer.back') }}</a>
        <header class="account-heading account-heading--detail">
            <div><span class="account-kicker">{{ __('reservation.customer.mine') }}</span>
                <h1>Chi tiết đặt bàn</h1>
                <p>Mã đặt bàn <span
                        title="{{ $reservation->reservation_code }}">#{{ \App\Support\DisplayCode::short($reservation->reservation_code) }}</span>
                </p>
            </div>
            @include('partials.reservation-status-badge', ['status' => $reservation->status])
        </header>
        <section class="account-card reservation-detail">
            <div class="reservation-detail-grid">
                <div>
                    <span>{{ __('reservation.fields.date_time') }}</span>
                    <strong>{{ substr($reservation->reservation_time, 0, 5) }}</strong>
                    <small>{{ $reservation->reservation_date->format('d/m/Y') }}</small>
                </div>
                <div><span>{{ __('reservation.fields.party_size') }}</span><strong>{{ $reservation->party_size }}
                        khách</strong></div>
                <div>
                    <span>{{ __('reservation.fields.table') }}</span><strong>{{ $reservation->table?->name ?: __('reservation.customer.table_not_assigned') }}</strong>
                </div>
            </div>
            @if ($reservation->preorder)
                <div class="reservation-preorder">
                    <h2>{{ __('customer_order.preorder_summary') }}</h2>
                    @foreach ($reservation->preorder->items as $item)
                        <div><span>{{ $item->product_name }} <small>×
                                    {{ $item->quantity }}</small></span><strong>{{ number_format($item->line_total) }}
                                ₫</strong></div>
                    @endforeach
                    <div class="reservation-preorder-total"><span>Tạm
                            tính</span><strong>{{ number_format($reservation->preorder->total_amount) }} ₫</strong></div>
                </div>
            @endif
        </section>
    </div>
@endsection
