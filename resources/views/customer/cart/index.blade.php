@extends('layouts.customer')
@section('title', __('customer_order.cart'))
@section('content')
    <h1>{{ __('customer_order.cart') }}</h1>
    <div class="alert alert-info">{{ __('customer_order.bound_context', ['session' => $diningSession->session_code, 'table' => $diningSession->table->code]) }}</div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if($rows === [])<div class="alert alert-secondary">{{ __('customer_order.empty') }}</div>@else
        <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('customer_order.product') }}</th><th>{{ __('customer_order.current_price') }}</th><th>{{ __('customer_order.quantity') }}</th><th>{{ __('customer_order.item_note') }}</th><th>{{ __('customer_order.total') }}</th><th></th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ $row['name'] }} @if(!$row['available'])<span class="badge text-bg-danger">{{ __('customer_order.unavailable') }}</span>@endif</td><td>{{ $row['price'] === null ? '—' : number_format($row['price'], 0, ',', '.').' ₫' }}</td><td colspan="2"><form class="d-flex gap-2" method="post" action="{{ route('customer.cart.items.update', $row['product_id']) }}">@csrf @method('patch')<input class="form-control" style="max-width:7rem" type="number" min="1" max="1000" name="quantity" value="{{ $row['quantity'] }}"><input class="form-control" name="note" value="{{ $row['note'] }}"><button class="btn btn-outline-primary">{{ __('app.save') }}</button></form></td><td>{{ $row['line_total'] === null ? '—' : number_format($row['line_total'], 0, ',', '.').' ₫' }}</td><td><form method="post" action="{{ route('customer.cart.items.destroy', $row['product_id']) }}">@csrf @method('delete')<button class="btn btn-outline-danger">{{ __('customer_order.remove') }}</button></form></td></tr>@endforeach
        </tbody><tfoot><tr><th colspan="4">{{ __('customer_order.review_total') }}</th><th>{{ number_format(collect($rows)->sum(fn($row) => $row['line_total'] ?? 0), 0, ',', '.') }} ₫</th><th></th></tr></tfoot></table></div>
        <form class="mb-3" method="post" action="{{ route('customer.cart.clear') }}">@csrf @method('delete')<button class="btn btn-outline-secondary">{{ __('customer_order.clear') }}</button></form>
        <form class="card card-body" method="post" action="{{ route('customer.cart.submit') }}">@csrf<label class="form-label" for="order-note">{{ __('customer_order.order_note') }}</label><textarea class="form-control mb-3" id="order-note" name="note">{{ old('note') }}</textarea><button class="btn btn-primary">{{ __('customer_order.submit') }}</button></form>
    @endif
@endsection
