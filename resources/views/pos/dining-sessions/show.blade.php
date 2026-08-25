@extends('layouts.pos')
@section('title', $diningSession->session_code)
@section('content')
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <h1>{{ $diningSession->session_code }}</h1>
    <dl class="row"><dt class="col-sm-3">{{ __('dining_session.fields.table') }}</dt><dd class="col-sm-9">{{ $diningSession->table->code }} — {{ $diningSession->table->name }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.customer') }}</dt><dd class="col-sm-9">{{ $diningSession->customer?->name ?: __('dining_session.anonymous') }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.reservation') }}</dt><dd class="col-sm-9">@if($diningSession->reservation)<a href="{{ route('pos.reservations.show', $diningSession->reservation) }}">{{ $diningSession->reservation->reservation_code }}</a>@else—@endif</dd><dt class="col-sm-3">{{ __('dining_session.fields.guests') }}</dt><dd class="col-sm-9">{{ $diningSession->guest_count }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.opened_by') }}</dt><dd class="col-sm-9">{{ $diningSession->openedBy->name }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.started_at') }}</dt><dd class="col-sm-9">{{ $diningSession->started_at->format('d/m/Y H:i') }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.status') }}</dt><dd class="col-sm-9">{{ __('dining_session.statuses.'.$diningSession->status->value) }}</dd><dt class="col-sm-3">{{ __('dining_session.fields.note') }}</dt><dd class="col-sm-9">{{ $diningSession->note ?: '—' }}</dd></dl>
    @can('billing.view')
        @if($diningSession->bill)
            <a class="btn btn-outline-success mb-3" href="{{ route('pos.bills.show', $diningSession->bill) }}">{{ __('billing.view_bill') }}</a>
        @elseif($diningSession->status === \App\Enums\DiningSessionStatus::Active)
            <form class="d-inline" method="post" action="{{ route('pos.billing.open', $diningSession) }}">@csrf<button class="btn btn-outline-success mb-3">{{ __('billing.open') }}</button></form>
        @endif
    @endcan
    @if($diningSession->status === \App\Enums\DiningSessionStatus::Active && auth()->user()->can('dining-session.view') && auth()->user()->can('order.create'))
        <form class="mb-3" method="post" action="{{ route('pos.dining-sessions.customer-access-link', $diningSession) }}">@csrf<button class="btn btn-outline-primary">{{ __('customer_order.create_access_link') }}</button></form>
    @endif
    @if(session('customer_access_url'))<div class="alert alert-success"><label class="form-label" for="customer-access-url">{{ __('customer_order.access_link') }}</label><input class="form-control" id="customer-access-url" readonly value="{{ session('customer_access_url') }}"></div>@endif
    @if($diningSession->status === \App\Enums\DiningSessionStatus::Active && auth()->user()->can('order.create'))<a class="btn btn-primary mb-4" href="{{ route('pos.orders.create', $diningSession) }}">{{ $diningSession->orders->isEmpty() ? __('order.create') : __('order.additional') }}</a>@endif
    <h2>{{ __('order.history') }}</h2>
    @forelse($diningSession->orders as $order)
        <section class="card mb-3"><div class="card-header d-flex justify-content-between"><strong>{{ $order->order_code }}</strong><span>{{ $order->ordered_at->format('d/m/Y H:i') }} · {{ __('order.sources.'.$order->source) }} · {{ $order->createdByEmployee?->name }}</span></div><div class="card-body">
            @if($order->note)<p>{{ $order->note }}</p>@endif
            <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('order.fields.product') }}</th><th>{{ __('order.fields.quantity') }}</th><th>{{ __('order.fields.price') }}</th><th>{{ __('order.fields.line_total') }}</th><th>{{ __('order.fields.status') }}</th><th>{{ __('order.fields.item_note') }}</th><th>{{ __('kitchen.actions') }}</th></tr></thead><tbody>
            @foreach($order->items as $item)
                <tr><td>{{ $item->product_name }}</td><td>@if($item->status === \App\Enums\OrderItemStatus::Waiting && auth()->user()->can('order.update'))<form class="d-flex gap-2" method="post" action="{{ route('pos.order-items.update', $item) }}">@csrf @method('patch')<input class="form-control" style="max-width:6rem" type="number" min="1" max="1000" name="quantity" value="{{ $item->quantity }}"><input class="form-control" name="note" value="{{ $item->note }}"><button class="btn btn-sm btn-outline-primary">{{ __('app.save') }}</button></form>@else{{ $item->quantity }}@endif</td><td>{{ number_format($item->unit_price, 0, ',', '.') }} ₫</td><td>{{ number_format($item->line_total, 0, ',', '.') }} ₫</td><td><span class="badge text-bg-secondary">{{ __('order.statuses.'.$item->status->value) }}</span></td><td>{{ $item->note ?: '—' }}</td><td>
                    @if($item->status === \App\Enums\OrderItemStatus::Ready && auth()->user()->can('order-item.mark-served'))
                        <form method="post" action="{{ route('pos.order-items.mark-served', $item) }}">@csrf @method('patch')<button class="btn btn-sm btn-success">{{ __('kitchen.mark_served') }}</button></form>
                    @endif
                    @if($item->status === \App\Enums\OrderItemStatus::Waiting && auth()->user()->can('order-item.cancel-waiting'))
                        @include('pos.orders._cancel-item-form', ['route' => route('pos.order-items.cancel-waiting', $item)])
                    @endif
                    @if($item->status === \App\Enums\OrderItemStatus::Preparing && auth()->user()->can('order-item.cancel-preparing'))
                        @include('pos.orders._cancel-item-form', ['route' => route('pos.order-items.cancel-preparing', $item)])
                    @endif
                </td></tr>
            @endforeach
            </tbody><tfoot><tr><th colspan="3">{{ __('order.subtotal') }}</th><th>{{ number_format($order->items->sum('line_total'), 0, ',', '.') }} ₫</th><th colspan="3"></th></tr></tfoot></table></div>
        </div></section>
    @empty<div class="alert alert-secondary">{{ __('order.empty') }}</div>@endforelse
@endsection
