@extends($adminContext ? 'layouts.admin' : 'layouts.pos')
@section('title', __('order.create'))
@section('content')
    <header class="page-heading">
        <div>
            <h1>{{ $diningSession->orders->isEmpty() ? __('order.create') : __('order.additional') }}</h1>
            <p><x-display-code :code="$diningSession->session_code" /> · {{ $diningSession->table->code }}</p>
        </div>
        <a class="btn btn-outline-secondary"
            href="{{ route($adminContext ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show', $diningSession) }}">{{ __('reservation.internal.back') }}</a>
    </header>
    <form method="post"
        action="{{ route($adminContext ? 'admin.dining-sessions.orders.store' : 'pos.orders.store', $diningSession) }}">
        @csrf
        <div class="order-form-grid">
            <aside class="order-form-sidebar"><label class="form-label"
                    for="note">{{ __('order.fields.order_note') }}</label>
                <textarea class="form-control" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                <div class="mt-3"><strong data-selected-count>0</strong> {{ __('order.selected_products') }}</div><button
                    class="btn btn-primary w-100 mt-3" @disabled($products->isEmpty())>{{ __('order.submit') }}</button>
            </aside>
            <section>
                <div class="filter-bar"><label class="form-label"
                        for="product-search">{{ __('order.search_products') }}</label><input class="form-control"
                        id="product-search" placeholder="{{ __('order.search_products') }}" autocomplete="off"></div>
                <div class="order-product-table table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('order.fields.product') }}</th>
                                <th>{{ __('order.fields.price') }}</th>
                                <th>{{ __('order.fields.quantity') }}</th>
                                <th>{{ __('order.fields.item_note') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $index => $product)
                                <tr
                                    data-product-row="{{ mb_strtolower($product->name . ' ' . $product->category->name) }}">
                                    <td>
                                        <div class="form-check"><input class="form-check-input"
                                                id="product-{{ $product->id }}" type="checkbox"
                                                data-order-toggle="{{ $index }}"><label class="form-check-label"
                                                for="product-{{ $product->id }}"><strong>{{ $product->name }}</strong><br><small
                                                    class="text-muted">{{ $product->category->name }}</small></label></div>
                                        <input type="hidden" name="items[{{ $index }}][product_id]"
                                            value="{{ $product->id }}" disabled data-order-field="{{ $index }}">
                                    </td>
                                    <td>{{ number_format($product->price, 0, ',', '.') }} ₫</td>
                                    <td style="width:8rem"><input class="form-control" type="number" min="1"
                                            max="1000" name="items[{{ $index }}][quantity]" value="1"
                                            disabled data-order-field="{{ $index }}"></td>
                                    <td><input class="form-control" name="items[{{ $index }}][note]" disabled
                                            data-order-field="{{ $index }}"></td>
                            </tr>@empty<tr>
                                    <td colspan="4">
                                        <div class="empty-state">{{ __('order.empty_products') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </form>
@endsection
@push('scripts')
    <script>
        const toggles = [...document.querySelectorAll('[data-order-toggle]')];
        const updateCount = () => document.querySelector('[data-selected-count]').textContent = toggles.filter(item => item
            .checked).length;
        toggles.forEach(toggle => toggle.addEventListener('change', () => {
            document.querySelectorAll(`[data-order-field="${toggle.dataset.orderToggle}"]`).forEach(field =>
                field.disabled = !toggle.checked);
            toggle.closest('tr').classList.toggle('is-selected', toggle.checked);
            updateCount()
        }));
        document.getElementById('product-search')?.addEventListener('input', event => document.querySelectorAll(
            '[data-product-row]').forEach(row => row.hidden = !row.dataset.productRow.includes(event.target.value
            .toLocaleLowerCase())));
    </script>
@endpush
