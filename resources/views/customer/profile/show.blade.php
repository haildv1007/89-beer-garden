@extends('layouts.customer')
@section('title', __('customer.profile.title').' — '.__('app.name'))
@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4"><div><h1>{{ __('customer.profile.title') }}</h1><p class="text-muted mb-0">{{ __('customer.profile.identity_explanation') }}</p></div><a class="btn btn-outline-primary" href="{{ route('customer.profile.edit', $customer) }}">{{ __('app.edit') }}</a></div>
    <div class="card mb-4"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-4">{{ __('customer.fields.name') }}</dt><dd class="col-sm-8">{{ $customer->name }}</dd>
        <dt class="col-sm-4">{{ __('customer.fields.email') }}</dt><dd class="col-sm-8">{{ $customer->email }}</dd>
        <dt class="col-sm-4">{{ __('customer.fields.phone') }}</dt><dd class="col-sm-8">{{ $customer->phone ?: '—' }}</dd>
    </dl></div></div>
    <h2 class="h4">{{ __('customer.history.title') }}</h2>
    <div class="row g-3"><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('customer.history.reservations') }}</div><div class="display-6">{{ $customer->reservations_count }}</div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('customer.history.sessions') }}</div><div class="display-6">{{ $customer->dining_sessions_count }}</div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('customer.history.orders') }}</div><div class="display-6">{{ $customer->orders_count }}</div></div></div></div></div>
    <p class="text-muted mt-3">{{ __('customer.history.details_later') }}</p>
@endsection
