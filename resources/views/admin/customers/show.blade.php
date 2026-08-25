@extends('layouts.admin')
@section('title', $customer->name)
@section('content')
    <h1>{{ $customer->name }}</h1><p><span class="badge {{ $customer->user ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $customer->user ? __('customer.admin.linked_account') : __('customer.admin.guest_profile') }}</span></p>
    <dl class="row">
        <dt class="col-sm-3">{{ __('customer.fields.phone') }}</dt><dd class="col-sm-9">{{ $customer->phone ?: '—' }}</dd>
        <dt class="col-sm-3">{{ __('customer.fields.email') }}</dt><dd class="col-sm-9">{{ $customer->email ?: '—' }}</dd>
        <dt class="col-sm-3">{{ __('customer.admin.account_email') }}</dt><dd class="col-sm-9">{{ $customer->user?->email ?: '—' }}</dd>
        <dt class="col-sm-3">{{ __('customer.admin.account_status') }}</dt><dd class="col-sm-9">{{ $customer->user ? __('customer.account_statuses.'.$customer->user->status) : '—' }}</dd>
        <dt class="col-sm-3">{{ __('customer.fields.created_at') }}</dt><dd class="col-sm-9">{{ $customer->created_at }}</dd>
        <dt class="col-sm-3">{{ __('customer.fields.updated_at') }}</dt><dd class="col-sm-9">{{ $customer->updated_at }}</dd>
        <dt class="col-sm-3">{{ __('customer.history.title') }}</dt><dd class="col-sm-9">{{ __('customer.admin.history_summary', ['reservations' => $customer->reservations_count, 'sessions' => $customer->dining_sessions_count, 'orders' => $customer->orders_count]) }}</dd>
    </dl>
    <a class="btn btn-outline-secondary" href="{{ route('admin.customers.index') }}">{{ __('customer.admin.back') }}</a>
    <hr><h2>{{ __('order_history.admin_title') }}</h2>
    @include('customer.orders._summary')
    @include('customer.orders._filters')
    @php($adminHistory = true)
    @include('customer.orders._list')
@endsection
