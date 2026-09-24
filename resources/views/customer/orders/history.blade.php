@extends('layouts.customer')
@section('title', __('order_history.title'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')<header class="account-heading">
            <div><h1>{{ __('order_history.title') }}</h1>
                <p>Theo dõi các hóa đơn đã thanh toán tại nhà hàng.</p>
            </div>
        </header>
        @include('customer.orders._summary')
        @include('customer.orders._filters')
        @include('customer.orders._list', ['transactions' => $transactions])
    </div>
@endsection
