@extends('layouts.pos')
@section('title', __('order.create'))
@section('content')
    <h1>{{ __('order.create') }}</h1><p>{{ $diningSession->session_code }}</p>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('pos.orders.store', $diningSession) }}">@csrf
        <div class="mb-3"><label class="form-label" for="note">{{ __('order.fields.order_note') }}</label><textarea class="form-control" id="note" name="note">{{ old('note') }}</textarea></div>
        <div class="mb-3"><input class="form-control" id="product-search" placeholder="{{ __('order.search_products') }}"></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>{{ __('order.fields.product') }}</th><th>{{ __('order.fields.price') }}</th><th>{{ __('order.fields.quantity') }}</th><th>{{ __('order.fields.item_note') }}</th></tr></thead><tbody>
        @forelse($products as $index => $product)<tr data-product-row="{{ mb_strtolower($product->name.' '.$product->category->name) }}"><td><div class="form-check"><input class="form-check-input" type="checkbox" data-order-toggle="{{ $index }}"><label class="form-check-label">{{ $product->name }} <small class="text-muted">({{ $product->category->name }})</small></label></div><input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}" disabled data-order-field="{{ $index }}"></td><td>{{ number_format($product->price, 0, ',', '.') }} ₫</td><td><input class="form-control" type="number" min="1" max="1000" name="items[{{ $index }}][quantity]" value="1" disabled data-order-field="{{ $index }}"></td><td><input class="form-control" name="items[{{ $index }}][note]" disabled data-order-field="{{ $index }}"></td></tr>@empty<tr><td colspan="4">{{ __('order.empty_products') }}</td></tr>@endforelse
        </tbody></table></div><button class="btn btn-primary" @disabled($products->isEmpty())>{{ __('order.submit') }}</button>
    </form>
    <script>document.querySelectorAll('[data-order-toggle]').forEach((toggle) => toggle.addEventListener('change', () => document.querySelectorAll(`[data-order-field="${toggle.dataset.orderToggle}"]`).forEach((field) => field.disabled = !toggle.checked)));document.getElementById('product-search')?.addEventListener('input', (event) => document.querySelectorAll('[data-product-row]').forEach((row) => row.hidden = !row.dataset.productRow.includes(event.target.value.toLocaleLowerCase())));</script>
@endsection
