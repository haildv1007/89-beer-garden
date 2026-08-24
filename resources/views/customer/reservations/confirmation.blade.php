@extends('layouts.customer')
@section('title', __('reservation.customer.confirmation_title').' — '.__('app.name'))
@section('content')
    <div class="alert alert-success"><h1 class="h3">{{ __('reservation.customer.confirmation_title') }}</h1><p>{{ __('reservation.customer.confirmation_message') }}</p><p class="mb-0"><strong>{{ __('reservation.fields.code') }}:</strong> <span class="font-monospace">{{ $reservationCode }}</span></p></div>
    <a class="btn btn-primary" href="{{ route('customer.menu.index') }}">{{ __('app.home.view_menu') }}</a>
@endsection
