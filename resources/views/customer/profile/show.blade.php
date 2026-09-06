@extends('layouts.customer')
@section('title', __('customer.profile.title') . ' — ' . __('app.name'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')
        <header class="account-heading">
            <div><span class="account-kicker">{{ __('customer_ui.account') }}</span>
                <h1>{{ __('customer.profile.title') }}</h1>
                <p>{{ __('customer_ui.profile_copy') }}</p>
            </div><a class="btn btn-outline-primary"
                href="{{ route('customer.profile.edit', $customer) }}">{{ __('app.edit') }}</a>
        </header>
        <div class="account-profile-grid">
            <section class="account-card account-identity">
                @if ($customer->avatar_path)
                    <img class="account-avatar" src="{{ Storage::disk('public')->url($customer->avatar_path) }}"
                    alt="Ảnh đại diện của {{ $customer->name }}">@else<div class="account-avatar" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</div>
                @endif
                <div>
                    <h2>{{ $customer->name }}</h2>
                    <dl>
                        <div>
                            <dt>{{ __('customer.fields.phone') }}</dt>
                            <dd>{{ $customer->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('customer.fields.email') }}</dt>
                            <dd>{{ $customer->email ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
            <section class="account-stats" aria-label="Tổng quan tài khoản"><a
                    href="{{ route('customer.reservations.index') }}"><strong>{{ $customer->reservations_count }}</strong><span>Lượt
                        đặt bàn</span></a><a
                    href="{{ route('customer.orders.history') }}"><strong>{{ $customer->dining_sessions_count }}</strong><span>Lần
                        dùng bữa</span></a><a
                    href="{{ route('customer.orders.history') }}"><strong>{{ $customer->orders_count }}</strong><span>Lượt
                        gọi món</span></a></section>
        </div>
    </div>
@endsection
