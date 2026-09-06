@extends('layouts.customer')
@section('title', 'Chi tiết lần dùng bữa — ' . __('app.name'))
@section('content')
    <div class="account-page">
        @include('customer.account._nav')<a class="account-back" href="{{ route('customer.orders.history') }}">←
            {{ __('customer_ui.back') }}</a>
        <header class="account-heading account-heading--detail">
            <div><span class="account-kicker">{{ $diningSession->table?->name ?: 'Dùng món tại quán' }}</span>
                <h1>Chi tiết lần dùng bữa</h1>
                <p>{{ $diningSession->started_at->format('H:i · d/m/Y') }}@if ($diningSession->ended_at)
                        — {{ $diningSession->ended_at->format('H:i') }}
                    @endif · Mã
                    #{{ \App\Support\DisplayCode::short($diningSession->session_code) }}</p>
            </div>
            @php
                $statusClass =
                    $diningSession->status === \App\Enums\DiningSessionStatus::Active
                        ? 'text-bg-warning'
                        : 'text-bg-dark';
            @endphp
            <span class="status-badge {{ $statusClass }}">
                {{ __('order_history.status.' . $diningSession->status->value) }}
            </span>
        </header>
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                @foreach ($diningSession->orders as $order)
                    <section class="account-card order-group mb-3">
                        <header><strong>Lượt gọi món
                                {{ $loop->iteration }}</strong><span>{{ $order->ordered_at->format('H:i · d/m/Y') }}</span><small
                                title="{{ $order->order_code }}">#{{ \App\Support\DisplayCode::short($order->order_code) }}</small>
                        </header>
                        <div class="responsive-data">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('order_history.product') }}</th>
                                        <th>{{ __('order_history.quantity') }}</th>
                                        <th>{{ __('order_history.unit_price') }}</th>
                                        <th>{{ __('order_history.line_total') }}</th>
                                        <th>{{ __('order_history.note') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td data-label="Món">{{ $item->product_name }}</td>
                                            <td data-label="Số lượng">{{ $item->quantity }}</td>
                                            <td data-label="Đơn giá">{{ number_format($item->unit_price) }} ₫</td>
                                            <td data-label="Thành tiền"><strong>{{ number_format($item->line_total) }}
                                                    ₫</strong></td>
                                            <td data-label="Ghi chú">{{ $item->note ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            </div>
            <div class="col-lg-4">
                @if ($diningSession->bill)
                    <aside class="money-summary position-sticky" style="top:8rem">
                        <h2 class="h4">{{ __('checkout.title') }}</h2>
                        <dl class="row">
                            <dt class="col-7">{{ __('order_history.subtotal') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($diningSession->bill->subtotal) }} ₫</dd>
                            <dt class="col-7">{{ __('order_history.discount') }}</dt>
                            <dd class="col-5 text-end">− {{ number_format($diningSession->bill->discount_amount) }} ₫</dd>
                            <dt class="col-7">{{ __('order_history.voucher') }}</dt>
                            <dd class="col-5 text-end">{{ $diningSession->bill->voucher?->code ?: '—' }}</dd>
                        </dl>
                        <div class="money-total d-flex justify-content-between">
                            <span>{{ __('order_history.total') }}</span><strong>{{ number_format($diningSession->bill->total_amount) }}
                                ₫</strong>
                        </div>
                        @if ($diningSession->bill->status === \App\Enums\BillStatus::Paid && $diningSession->bill->successfulPayment)
                            <p class="small mt-3 mb-0">{{ __('order_history.payment') }}:
                                {{ __('order_history.methods.' . $diningSession->bill->successfulPayment->method) }} ·
                            {{ $diningSession->bill->successfulPayment->paid_at->format('H:i · d/m/Y') }}</p>@else<p
                                class="small mt-3 mb-0">{{ __('order_history.not_paid') }}</p>
                        @endif
                    </aside>
                @else<div class="empty-state">{{ __('order_history.no_bill') }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
