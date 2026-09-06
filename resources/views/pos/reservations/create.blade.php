@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Thêm đặt bàn')
@section('content')
    @php
        $prefix = $adminContext ?? false ? 'admin' : 'pos';
    @endphp
    <div class="manual-entry-page">
        <header class="admin-section-heading">
            <div><span class="admin-page-eyebrow">Nhập từ điện thoại, Zalo, Messenger</span>
                <h1>Thêm đặt bàn</h1>
                <p>Tạo yêu cầu đặt bàn thủ công. Nhân viên có thể xác nhận và xếp bàn sau.</p>
            </div><a class="btn btn-outline-secondary" href="{{ route($prefix . '.reservations.index') }}">Quay lại</a>
        </header>
        <form class="manual-entry-card" method="post" action="{{ route($prefix . '.reservations.store') }}"
            data-manual-reservation>@csrf
            <div class="manual-entry-grid"><label><span>Họ tên khách</span><input class="form-control" name="name"
                        value="{{ old('name') }}" required></label><label><span>Số điện thoại</span><input
                        class="form-control" name="phone" value="{{ old('phone') }}" required></label><label><span>Ngày
                        đến</span><input class="form-control" type="date" name="reservation_date"
                        min="{{ today()->format('Y-m-d') }}" value="{{ old('reservation_date', today()->format('Y-m-d')) }}"
                        required></label><label><span>Giờ đến</span><input class="form-control" type="time"
                        name="reservation_time" value="{{ old('reservation_time') }}" required></label><label><span>Số
                        khách</span><input class="form-control" type="number" name="party_size" min="1"
                        value="{{ old('party_size', 2) }}" required></label><label class="wide"><span>Ghi chú</span>
                    <textarea class="form-control" name="note" rows="3" placeholder="Vị trí mong muốn, ghế trẻ em, sinh nhật...">{{ old('note') }}</textarea>
                </label></div>
            <section class="manual-items">
                <header>
                    <div>
                        <h2>Món đặt trước <small>(không bắt buộc)</small></h2>
                        <p>Thêm món khách đã đặt trước qua điện thoại hoặc tin nhắn.</p>
                    </div><button class="btn btn-outline-primary" type="button" data-add-item>＋ Thêm món</button>
                </header>
                <div data-items></div>
                <div class="manual-order-total"><span>Tạm tính món</span><strong data-order-total>0 ₫</strong></div>
            </section>
            <template data-item-template>
                <div class="manual-item-row"><select class="form-select" data-field="product_id" required>
                        <option value="">Chọn món…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }} ·
                                {{ number_format($product->price) }} ₫</option>
                        @endforeach
                    </select><input class="form-control" data-field="quantity" type="number" min="1" max="100"
                        value="1" aria-label="Số lượng"><input class="form-control" data-field="note"
                        placeholder="Ghi chú món"><button class="btn btn-outline-danger" type="button"
                        data-remove-item>×</button></div>
            </template>
            <div class="manual-entry-actions"><button class="btn btn-primary">Tạo đặt bàn</button><a class="btn btn-light"
                    href="{{ route($prefix . '.reservations.index') }}">Hủy</a></div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-manual-reservation]'),
                items = form.querySelector('[data-items]'),
                template = form.querySelector('[data-item-template]'),
                total = form.querySelector('[data-order-total]');
            let index = 0;
            const refresh = () => {
                let sum = 0;
                items.querySelectorAll('.manual-item-row').forEach(row => {
                    const option = row.querySelector('select').selectedOptions[0],
                        quantity = Number(row.querySelector('input[type=number]').value || 0);
                    sum += Number(option?.dataset.price || 0) * quantity
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
                refresh()
            };
            form.querySelector('[data-add-item]').addEventListener('click', add);
            items.addEventListener('click', e => {
                const button = e.target.closest('[data-remove-item]');
                if (button) {
                    button.closest('.manual-item-row').remove();
                    refresh()
                }
            });
            items.addEventListener('input', refresh);
            items.addEventListener('change', refresh)
        });
    </script>
@endsection
