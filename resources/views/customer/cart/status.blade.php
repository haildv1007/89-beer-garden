@extends('layouts.customer')
@section('title', __('customer_order.current_status'))
@section('content')
    <h1>{{ __('customer_order.current_status') }}</h1>
    <div class="alert alert-info">{{ __('customer_order.bound_context', ['session' => $diningSession->session_code, 'table' => $diningSession->table->code]) }}</div>
    @forelse($diningSession->orders as $order)<section class="card mb-3"><div class="card-header">{{ $order->order_code }} · {{ $order->ordered_at->format('H:i d/m/Y') }}</div><ul class="list-group list-group-flush">@foreach($order->items as $item)<li class="list-group-item d-flex justify-content-between"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="badge text-bg-secondary">{{ __('order.statuses.'.$item->status->value) }}</span></li>@endforeach</ul></section>@empty<div class="alert alert-secondary">{{ __('customer_order.no_orders') }}</div>@endforelse
@endsection
