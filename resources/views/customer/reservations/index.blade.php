@extends('layouts.customer')
@section('title', __('reservation.customer.mine').' — '.__('app.name'))
@section('content')
    <h1>{{ __('reservation.customer.mine') }}</h1>
    @if ($reservations->isEmpty())<div class="alert alert-info">{{ __('reservation.customer.empty') }}</div>@else<div class="list-group">@foreach ($reservations as $reservation)<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('customer.reservations.show', $reservation) }}"><span><strong>{{ $reservation->reservation_code }}</strong><br>{{ $reservation->reservation_date->format('d/m/Y') }} {{ substr($reservation->reservation_time, 0, 5) }} · {{ trans_choice('reservation.people', $reservation->party_size, ['count' => $reservation->party_size]) }}</span>@include('partials.reservation-status-badge', ['status' => $reservation->status])</a>@endforeach</div><div class="mt-3">{{ $reservations->links() }}</div>@endif
@endsection
