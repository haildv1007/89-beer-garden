@extends('layouts.customer')
@section('title', __('order_history.title'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')<header class="account-heading">
            <div><span class="account-kicker">{{ __('customer_ui.account') }}</span>
                <h1>{{ __('order_history.title') }}</h1>
                <p>{{ __('customer_ui.history_copy') }}</p>
            </div>
            @if ($overview['last_used_at'])
                <p class="account-last-visit">{{ __('order_history.last_visit', ['date' => \Illuminate\Support\Carbon::parse($overview['last_used_at'])->format('d/m/Y')]) }}</p>
            @endif
        </header>
        @include('customer.orders._summary')
        @include('customer.orders._filters')
        @include('customer.orders._list')
    </div>
@endsection
