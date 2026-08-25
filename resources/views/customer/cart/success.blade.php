@extends('layouts.customer')
@section('title', __('customer_order.success'))
@section('content')
    <h1>{{ __('customer_order.success') }}</h1>
    @if($orderCode)<div class="alert alert-success">{{ __('customer_order.success_code', ['code' => $orderCode]) }}</div>@else<div class="alert alert-secondary">{{ __('customer_order.no_confirmation') }}</div>@endif
    <a class="btn btn-primary" href="{{ route('customer.orders.current') }}">{{ __('customer_order.current_status') }}</a>
@endsection
