@extends('layouts.customer')
@section('title', __('order_history.title'))
@section('content')
<h1>{{ __('order_history.title') }}</h1>
@include('customer.orders._summary')
@include('customer.orders._filters')
@include('customer.orders._list')
@endsection
