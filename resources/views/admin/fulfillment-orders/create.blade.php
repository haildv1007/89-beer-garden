@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Tạo đơn ngoài quán')
@section('content')
    @php
        $prefix = $adminContext ?? false ? 'admin' : 'pos';
    @endphp
    <div class="manual-entry-page">
        <header class="admin-section-heading">
            <div><span class="admin-page-eyebrow">Nhập từ điện thoại, Zalo, Messenger</span>
                <h1>Tạo đơn ngoài quán</h1>
                <p>Nhập thông tin nhận hàng và chọn món. Đơn mới sẽ ở trạng thái chờ xác nhận.</p>
            </div><a class="btn btn-outline-secondary" href="{{ route($prefix . '.fulfillment-orders.index') }}">Quay lại</a>
        </header>
        <form class="manual-entry-card" method="post" action="{{ route($prefix . '.fulfillment-orders.store') }}"
            data-manual-order>@csrf
            <div class="manual-entry-grid"><label><span>Hình thức</span><select class="form-select" name="fulfillment_type"
                        data-fulfillment-type>
                        <option value="pickup">Nhận tại quán</option>
                        <option value="delivery" @selected(old('fulfillment_type') === 'delivery')>Giao tận nơi</option>
                    </select></label><label><span>Khách chọn thanh toán</span><select class="form-select"
                        name="payment_option">
                        <option value="pay_on_receipt">Thanh toán khi nhận hàng</option>
                        <option value="bank_transfer" @selected(old('payment_option') === 'bank_transfer')>Chuyển khoản ngân hàng</option>
                    </select></label><label><span>Họ tên khách</span><input class="form-control" name="customer_name"
                        value="{{ old('customer_name') }}" required></label><label><span>Số điện thoại</span><input
                        class="form-control" name="phone" value="{{ old('phone') }}" required></label><label><span>Thời
                        gian nhận</span><input class="form-control" type="datetime-local" name="requested_for"
                        value="{{ old('requested_for', now()->addHour()->format('Y-m-d\TH:i')) }}" required></label><label
                    class="wide" data-delivery-address><span>Địa chỉ giao hàng</span><input class="form-control"
                        name="delivery_address" value="{{ old('delivery_address') }}"></label><label
                    class="wide"><span>Ghi chú đơn</span>
                    <textarea class="form-control" name="note" rows="2">{{ old('note') }}</textarea>
                </label></div>
            <section class="manual-items">
                <header>
                    <div>
                        <h2>Món trong đơn</h2>
                        <p>Thêm món, số lượng và ghi chú khẩu vị.</p>
                    </div><button class="btn btn-outline-primary" type="button" data-add-item>＋ Thêm món</button>
                </header>
                <div data-items></div>
                <div class="manual-order-total"><span>Tạm tính món</span><strong data-order-total>0 ₫</strong></div>
            </section>
            <template data-item-template>
                <div class="manual-item-row"><select class="form-select" data-field="product_id" required>
                        <option value="">Chọn món…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                data-variants='@json($product->availableVariants->map->only(["id", "name", "price"]))'>{{ $product->name }} ·
                                {{ $product->display_price }}</option>
                        @endforeach
                    </select><select class="form-select" data-field="variant_id" data-variant-select hidden disabled></select><input class="form-control" data-field="quantity" type="number" min="1" max="100"
                        value="1" aria-label="Số lượng"><input class="form-control" data-field="note"
                        placeholder="Ghi chú món"><button class="btn btn-outline-danger" type="button"
                        data-remove-item>×</button></div>
            </template>
            <div class="manual-entry-actions"><button class="btn btn-primary">Tạo đơn</button><a class="btn btn-light"
                    href="{{ route($prefix . '.fulfillment-orders.index') }}">Hủy</a></div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-manual-order]'),
                items = form.querySelector('[data-items]'),
                template = form.querySelector('[data-item-template]'),
                type = form.querySelector('[data-fulfillment-type]'),
                address = form.querySelector('[data-delivery-address]'),
                total = form.querySelector('[data-order-total]');
            let index = 0;
            const refreshVariant = row => {
                const product = row.querySelector('[name$="[product_id]"]'), variant = row.querySelector('[name$="[variant_id]"]');
                const variants = JSON.parse(product.selectedOptions[0]?.dataset.variants || '[]');
                variant.innerHTML = variants.map(item => `<option value="${item.id}" data-price="${item.price}">${item.name} · ${new Intl.NumberFormat('vi-VN').format(item.price)} ₫</option>`).join('');
                variant.hidden = variants.length === 0; variant.disabled = variants.length === 0; variant.required = variants.length > 0;
            };
            const refreshTotal = () => {
                let sum = 0;
                items.querySelectorAll('.manual-item-row').forEach(row => {
                    const product = row.querySelector('[name$="[product_id]"]'), variant = row.querySelector('[name$="[variant_id]"]');
                    sum += Number((!variant.disabled && variant.selectedOptions[0]?.dataset.price) || product.selectedOptions[0]?.dataset.price || 0) *
                        Number(row.querySelector('input[type=number]').value || 0)
                });
                total.textContent = new Intl.NumberFormat('vi-VN').format(sum) + ' ₫'
            };
            const add = () => {
                const fragment = template.content.cloneNode(true);
                fragment.querySelectorAll('[data-field]').forEach(el => {
                    el.name = `items[${index}][${el.dataset.field}]`;
                    el.removeAttribute('data-field')
                });
                index++;
                items.append(fragment);
                refreshTotal()
            };
            form.querySelector('[data-add-item]').addEventListener('click', add);
            items.addEventListener('click', e => {
                const button = e.target.closest('[data-remove-item]');
                if (button) {
                    button.closest('.manual-item-row').remove();
                    refreshTotal()
                }
            });
            items.addEventListener('input', refreshTotal);
            items.addEventListener('change', e => { if (e.target.name?.endsWith('[product_id]')) refreshVariant(e.target.closest('.manual-item-row')); refreshTotal() });
            const refresh = () => {
                address.hidden = type.value !== 'delivery';
                address.querySelector('input').required = type.value === 'delivery'
            };
            type.addEventListener('change', refresh);
            refresh();
            add()
        });
    </script>
@endsection
