@extends('layouts.customer')
@section('title', $reservation->reservation_code.' — '.__('app.name'))
@section('content')
    <div class="d-flex justify-content-between align-items-start"><div><h1>{{ $reservation->reservation_code }}</h1>@include('partials.reservation-status-badge', ['status' => $reservation->status])</div><a class="btn btn-outline-secondary" href="{{ route('customer.reservations.index') }}">{{ __('reservation.customer.back') }}</a></div>
    <dl class="row mt-4"><dt class="col-sm-4">{{ __('reservation.fields.date_time') }}</dt><dd class="col-sm-8">{{ $reservation->reservation_date->format('d/m/Y') }} {{ substr($reservation->reservation_time, 0, 5) }}</dd><dt class="col-sm-4">{{ __('reservation.fields.party_size') }}</dt><dd class="col-sm-8">{{ $reservation->party_size }}</dd><dt class="col-sm-4">{{ __('reservation.fields.table') }}</dt><dd class="col-sm-8">{{ $reservation->table ? $reservation->table->code.' — '.$reservation->table->name : __('reservation.customer.table_not_assigned') }}</dd></dl>
    <p class="text-muted">{{ __('reservation.customer.processing_notice') }}</p>
@endsection
