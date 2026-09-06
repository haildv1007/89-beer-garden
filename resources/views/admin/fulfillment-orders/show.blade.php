@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Chi tiết đơn ngoài quán')
@section('content')
    @php
        $fulfillmentRoutes = $adminContext ?? false ? 'admin.fulfillment-orders' : 'pos.fulfillment-orders';
    @endphp
    @php
        $fulfillmentLabel = $fulfillmentOrder->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán';
        $paymentBadgeClass = $fulfillmentOrder->payment_status === 'paid' ? 'success' : 'warning';
        $paymentLabel = $fulfillmentOrder->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán';
        $statusBadgeClass = match ($fulfillmentOrder->status) {
            'pending' => 'warning',
            'confirmed' => 'success',
            default => 'secondary',
        };
    @endphp
    <div class="fulfillment-detail-page ops-detail-page">
        <header class="admin-record-header fulfillment-record-header fulfillment-record-header--compact">
            <div class="fulfillment-record-main">
                <div class="fulfillment-record-meta">
                    <a class="admin-back-link" href="{{ route($fulfillmentRoutes . '.index') }}">← Danh sách</a>
                    <code class="fulfillment-record-code">
                        <x-display-code :code="$fulfillmentOrder->order_code" />
                    </code>
                    <span>{{ $fulfillmentLabel }}</span>
                    <span>{{ $fulfillmentOrder->placed_at->format('H:i · d/m/Y') }}</span>
                </div>
            </div>
            <div class="fulfillment-record-status">
                <span class="status-badge text-bg-{{ $paymentBadgeClass }}">{{ $paymentLabel }}</span>
                <span class="status-badge text-bg-{{ $statusBadgeClass }}">
                    {{ __('fulfillment_order.statuses.' . $fulfillmentOrder->status) }}
                </span>
            </div>
        </header>

        <section class="fulfillment-detail-overview">
            <div class="fulfillment-contact-card">
                <header><span>Thông tin nhận đơn</span><strong>{{ $fulfillmentOrder->customer_name }}</strong></header>
                <dl>
                    <div>
                        <dt>Số điện thoại</dt>
                        <dd>{{ $fulfillmentOrder->phone }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $fulfillmentOrder->email ?: 'Chưa cung cấp' }}</dd>
                    </div>
                    <div>
                        <dt>Thời gian nhận</dt>
                        <dd>{{ $fulfillmentOrder->requested_for->format('H:i · d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt>Hình thức</dt>
                        <dd>{{ $fulfillmentOrder->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}
                        </dd>
                    </div>
                    @if ($fulfillmentOrder->fulfillment_type === 'delivery')
                        <div class="wide fulfillment-address">
                            <dt>Địa chỉ giao hàng</dt>
                            <dd>{{ $fulfillmentOrder->delivery_address ?: 'Chưa có địa chỉ giao hàng' }}</dd>
                        </div>
                    @endif
                    <div class="wide">
                        <dt>Ghi chú đơn</dt>
                        <dd>{{ $fulfillmentOrder->note ?: 'Không có ghi chú' }}</dd>
                    </div>
                </dl>
            </div>
            <aside class="fulfillment-payment-stack">
                <section class="fulfillment-payment-card"><span>Thanh toán</span>
                    <dl>
                        <div>
                            <dt>Khách lựa chọn</dt>
                            <dd>{{ $fulfillmentOrder->payment_option === 'bank_transfer' ? 'Chuyển khoản' : 'Thanh toán khi nhận' }}
                                @if ($fulfillmentOrder->payment_reported_at && $fulfillmentOrder->payment_status !== 'paid')
                                    <small class="d-block text-warning">Khách báo đã chuyển · chờ kiểm tra</small>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Tạm tính</dt>
                            <dd>{{ number_format($fulfillmentOrder->subtotal) }} ₫</dd>
                        </div>
                        <div>
                            <dt>Giảm giá</dt>
                            <dd>{{ $fulfillmentOrder->discount_amount > 0 ? '− ' . number_format($fulfillmentOrder->discount_amount) : '0' }}
                                ₫</dd>
                        </div>
                        @if ($fulfillmentOrder->fulfillment_type === 'delivery')
                            <div>
                                <dt>Phí giao hàng</dt>
                                <dd>{{ number_format($fulfillmentOrder->shipping_fee) }} ₫</dd>
                            </div>
                        @endif
                        <div class="total">
                            <dt>Tổng cộng</dt>
                            <dd>{{ number_format($fulfillmentOrder->total_amount) }} ₫</dd>
                        </div>
                        @if ($fulfillmentOrder->payment_status === 'paid')
                            <div>
                                <dt>Đã thu bằng</dt>
                                <dd>{{ $fulfillmentOrder->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' }}</dd>
                            </div>
                            <div>
                                <dt>Người thu</dt>
                                <dd>{{ $fulfillmentOrder->paidByEmployee?->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
                @if ($fulfillmentOrder->status !== 'rejected')
                    <section class="fulfillment-checkout-card fulfillment-checkout-card--compact">
                        @if ($fulfillmentOrder->payment_status === 'paid')
                            <div><span>ĐÃ THANH TOÁN</span>
                                <h2>{{ number_format($fulfillmentOrder->total_amount) }} ₫</h2>
                                <p>{{ $fulfillmentOrder->paid_at->format('H:i · d/m/Y') }} ·
                                    {{ $fulfillmentOrder->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản ngân hàng' }}
                                </p>
                            </div><a class="btn btn-primary"
                                href="{{ route($fulfillmentRoutes . '.invoice', $fulfillmentOrder) }}">In hóa đơn</a>
                        @else
                            <div><span>THU TIỀN ĐƠN HÀNG</span>
                                <h2>{{ number_format($fulfillmentOrder->total_amount) }} ₫</h2>
                                <p>{{ $fulfillmentOrder->payment_option === 'bank_transfer' ? 'Khách đã chọn chuyển khoản ngân hàng.' : 'Thu tiền khi khách nhận đơn.' }}
                                </p>
                            </div>
                            @can('payment.complete')
                                <form method="post"
                                    action="{{ route($fulfillmentRoutes . '.payment.complete', $fulfillmentOrder) }}"
                                    data-fulfillment-payment>@csrf<div><label>Phương thức đã thu</label><select
                                            class="form-select" name="payment_method" data-payment-method>
                                            @if ($fulfillmentOrder->payment_option === 'pay_on_receipt')
                                                <option value="cash">Tiền mặt</option>
                                            @endif
                                            <option value="bank_transfer">
                                                Chuyển khoản ngân hàng</option>
                                        </select></div>
                                    <div data-cash-field><label>Tiền khách đưa</label><input class="form-control" type="number"
                                            min="0" name="cash_received" value="{{ $fulfillmentOrder->total_amount }}"
                                            data-cash-received><small>Tiền trả lại: <strong data-cash-change>0
                                                ₫</strong></small></div><label class="billing-confirm-check"><input
                                            type="checkbox" name="confirmed_received" value="1" required><span>Xác nhận đã
                                            nhận đủ tiền</span></label><button class="btn btn-primary">Xác nhận thanh
                                        toán</button>
                                </form>
                            @endcan
                        @endif
                    </section>
                @endif
            </aside>
        </section>

        <section class="admin-order-items fulfillment-items">
            <div class="admin-section-heading">
                <div>
                    <h2>Món đã đặt</h2>
                    <p>{{ $fulfillmentOrder->items->sum('quantity') }} phần · {{ $fulfillmentOrder->items->count() }} loại
                        món</p>
                </div>
            </div>
            @foreach ($fulfillmentOrder->items as $item)
                <article>
                    <div class="admin-order-item-image">
                        @if ($item->product?->primary_image_url)
                        <img src="{{ $item->product->primary_image_url }}" alt="">@else<span>🍽</span>
                        @endif
                    </div>
                    <div class="admin-order-item-name">
                        <strong>{{ $item->product_name }}</strong><small>{{ $item->note ?: 'Không có ghi chú món' }}</small>
                    </div><span class="fulfillment-item-quantity">×
                        {{ $item->quantity }}</span><strong>{{ number_format($item->line_total) }} ₫</strong>
                </article>
            @endforeach
        </section>
        @if ($fulfillmentOrder->status === 'pending' && $fulfillmentOrder->payment_status !== 'paid')
            <section class="fulfillment-editor" data-fulfillment-editor hidden>
                <div class="fulfillment-editor__heading">
                    <div>
                        <h2>Chỉnh sửa đơn</h2>
                        <p>Cập nhật thông tin khách và món trước khi gửi xuống bếp.</p>
                    </div><button class="btn btn-light" type="button" data-cancel-fulfillment-editor>Hủy</button>
                </div>
                <form method="post" action="{{ route($fulfillmentRoutes . '.update', $fulfillmentOrder) }}">@csrf
                    @method('put')
                    <div class="fulfillment-editor__fields">
                        <label><span>Họ và tên</span><input class="form-control" name="customer_name"
                                value="{{ $fulfillmentOrder->customer_name }}" required></label>
                        <label><span>Số điện thoại</span><input class="form-control" name="phone"
                                value="{{ $fulfillmentOrder->phone }}" required></label>
                        <label><span>Email</span><input class="form-control" type="email" name="email"
                                value="{{ $fulfillmentOrder->email }}"></label>
                        <label><span>Thời gian nhận</span><input class="form-control" type="datetime-local"
                                name="requested_for" value="{{ $fulfillmentOrder->requested_for->format('Y-m-d\\TH:i') }}"
                                required></label>
                        <label><span>Khách chọn thanh toán</span><select class="form-select" name="payment_option">
                                <option value="pay_on_receipt" @selected($fulfillmentOrder->payment_option === 'pay_on_receipt')>Thanh toán khi nhận hàng
                                </option>
                                <option value="bank_transfer" @selected($fulfillmentOrder->payment_option === 'bank_transfer')>Chuyển khoản ngân hàng</option>
                            </select></label>
                        @if ($fulfillmentOrder->fulfillment_type === 'delivery')
                            <label class="wide"><span>Địa chỉ giao hàng</span>
                                <textarea class="form-control" name="delivery_address" rows="2" required>{{ $fulfillmentOrder->delivery_address }}</textarea>
                            </label>
                        @endif
                        <label class="wide"><span>Ghi chú đơn</span>
                            <textarea class="form-control" name="note" rows="2">{{ $fulfillmentOrder->note }}</textarea>
                        </label>
                    </div>
                    <div class="fulfillment-editor__items">
                        <div class="fulfillment-editor__items-head">
                            <div>
                                <h3>Món trong đơn</h3>
                                <p>Giá sẽ được tính lại theo giá sản phẩm hiện tại.</p>
                            </div><button class="btn btn-outline-primary" type="button" data-add-fulfillment-item>＋ Thêm
                                món</button>
                        </div>
                        <div data-fulfillment-items>
                            @foreach ($fulfillmentOrder->items as $index => $item)
                                <div class="fulfillment-editor__item">
                                    <select class="form-select" name="items[{{ $index }}][product_id]" required>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected($item->product_id === $product->id)>
                                                {{ $product->name }} · {{ number_format($product->price) }} ₫</option>
                                        @endforeach
                                    </select>
                                    <input class="form-control" type="number" name="items[{{ $index }}][quantity]"
                                        min="1" max="100" value="{{ $item->quantity }}" aria-label="Số lượng"
                                        required>
                                    <input class="form-control" name="items[{{ $index }}][note]"
                                        value="{{ $item->note }}" placeholder="Ghi chú món">
                                    <button class="btn btn-outline-danger" type="button" data-remove-fulfillment-item
                                        aria-label="Xóa món">×</button>
                                </div>
                            @endforeach
                        </div>
                        <template data-fulfillment-item-template>
                            <div class="fulfillment-editor__item"><select class="form-select" data-field="product_id"
                                    required>
                                    <option value="">Chọn món…</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ·
                                            {{ number_format($product->price) }} ₫</option>
                                    @endforeach
                                </select><input class="form-control" data-field="quantity" type="number" min="1"
                                    max="100" value="1" aria-label="Số lượng" required><input
                                    class="form-control" data-field="note" placeholder="Ghi chú món"><button
                                    class="btn btn-outline-danger" type="button" data-remove-fulfillment-item
                                    aria-label="Xóa món">×</button></div>
                        </template>
                    </div>
                    <div class="fulfillment-editor__actions"><button class="btn btn-primary" type="submit">Lưu thay
                            đổi</button><button class="btn btn-light" type="button"
                            data-cancel-fulfillment-editor>Hủy</button><span data-fulfillment-feedback role="status"
                            aria-live="polite"></span></div>
                </form>
            </section>
            <section class="admin-record-actions fulfillment-actions"><button class="btn btn-outline-primary"
                    type="button" data-open-fulfillment-editor>Chỉnh sửa đơn</button>
                <form method="post" action="{{ route($fulfillmentRoutes . '.confirm', $fulfillmentOrder) }}">@csrf
                    @method('patch')<button class="btn btn-success">Xác nhận và gửi bếp</button></form>
                <form class="admin-reject-form" method="post"
                    action="{{ route($fulfillmentRoutes . '.reject', $fulfillmentOrder) }}">@csrf @method('patch')<input
                        class="form-control" name="reason" required maxlength="1000"
                        placeholder="Nhập lý do từ chối"><button class="btn btn-outline-danger">Từ chối</button></form>
            </section>
        @elseif($fulfillmentOrder->status === 'pending' && $fulfillmentOrder->payment_status === 'paid')
            <section class="admin-record-actions fulfillment-actions">
                <form method="post" action="{{ route($fulfillmentRoutes . '.confirm', $fulfillmentOrder) }}">@csrf
                    @method('patch')<button class="btn btn-success">Xác nhận và gửi bếp</button></form><span
                    class="text-muted">Đơn đã thu tiền nên không thể sửa hoặc từ chối.</span>
            </section>
        @elseif($fulfillmentOrder->status === 'rejected' && $fulfillmentOrder->rejection_reason)
            <div class="alert alert-secondary mt-3">Lý do từ chối: {{ $fulfillmentOrder->rejection_reason }}</div>
        @endif
    </div>
    <script>
        document.addEventListener('click', (event) => {
            const page = event.target.closest('.fulfillment-detail-page');
            if (!page) return;
            const editor = page.querySelector('[data-fulfillment-editor]');
            if (event.target.closest('[data-open-fulfillment-editor]')) {
                editor.hidden = false;
                editor.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                return;
            }
            if (event.target.closest('[data-cancel-fulfillment-editor]')) {
                editor.hidden = true;
                return;
            }
            if (event.target.closest('[data-remove-fulfillment-item]')) {
                event.target.closest('.fulfillment-editor__item')?.remove();
                return;
            }
            const add = event.target.closest('[data-add-fulfillment-item]');
            if (add) {
                const template = editor.querySelector('[data-fulfillment-item-template]');
                const items = editor.querySelector('[data-fulfillment-items]');
                const fragment = template.content.cloneNode(true);
                const index = Date.now();
                fragment.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = `items[${index}][${field.dataset.field}]`;
                    field.removeAttribute('data-field');
                });
                items.append(fragment);
                items.querySelector('.fulfillment-editor__item:last-child select')?.focus();
            }
        });
        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('.fulfillment-editor form');
            if (!form) return;
            event.preventDefault();
            const submit = form.querySelector('button[type="submit"]');
            const feedback = form.querySelector('[data-fulfillment-feedback]');
            submit.disabled = true;
            submit.textContent = 'Đang lưu…';
            feedback.textContent = '';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? result
                    .message ?? 'Không thể cập nhật đơn.');
                const refreshed = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const copy = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
                document.querySelector('.fulfillment-detail-page')?.replaceWith(copy.querySelector(
                    '.fulfillment-detail-page'));
            } catch (error) {
                feedback.textContent = error.message;
                submit.disabled = false;
                submit.textContent = 'Lưu thay đổi';
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-fulfillment-payment]');
            if (!form) return;
            const method = form.querySelector('[data-payment-method]'),
                field = form.querySelector('[data-cash-field]'),
                received = form.querySelector('[data-cash-received]'),
                change = form.querySelector('[data-cash-change]'),
                total = {{ (int) $fulfillmentOrder->total_amount }};
            const refresh = () => {
                const cash = method.value === 'cash';
                field.hidden = !cash;
                received.disabled = !cash;
                change.textContent = new Intl.NumberFormat('vi-VN').format(Math.max(0, Number(received.value ||
                    0) - total)) + ' ₫'
            };
            method.addEventListener('change', refresh);
            received.addEventListener('input', refresh);
            refresh()
        });
    </script>
@endsection
