@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Chi tiết phiên phục vụ')
@section('content')
    @php
        $active = $diningSession->status->value === 'active';
        $billableItems = $diningSession->orders->flatMap->items->reject(
            fn($item) => $item->status === \App\Enums\OrderItemStatus::Cancelled,
        );
        $itemsTotal = $billableItems->sum('line_total');
        $operationPrefix = $adminContext ?? false ? 'admin' : 'pos';
        $orderStoreRoute = $adminContext ?? false ? 'admin.dining-sessions.orders.store' : 'pos.orders.store';
    @endphp

    <div class="dining-detail-page ops-detail-page">
        <a class="admin-back-link" href="{{ route($operationPrefix . '.dining-sessions.index') }}">← Danh sách phiên</a>
        <header class="admin-record-header dining-session-header">
            <div>
                <h1>{{ $diningSession->table->name }}</h1>
                <p><x-display-code :code="$diningSession->session_code" /> · Bắt đầu {{ $diningSession->started_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="dining-session-header__actions"><span
                    class="status-badge text-bg-{{ $active ? 'warning' : 'success' }}">{{ __('dining_session.statuses.' . $diningSession->status->value) }}</span>
                @if ($active) @can('billing.view')
                    @if ($billableItems->isNotEmpty())
                        <form method="post"
                            action="{{ route($adminContext ?? false ? 'admin.dining-sessions.billing.open' : 'pos.billing.open', $diningSession) }}">
                            @csrf<button
                                class="btn btn-primary">{{ $diningSession->bill ? 'Cập nhật thanh toán' : 'Mở thanh toán' }}</button>
                    </form>@else<button class="btn btn-primary" disabled>Mở thanh toán</button>
                    @endif
                @endcan
            @endif
        </div>
    </header>

    <div class="dining-service-overview">
        <section class="dining-guest-card">
            <header>
                <div><span>Bàn & khách hàng</span>
                    <h2>{{ $diningSession->customer?->name ?: 'Khách không định danh' }}</h2>
                </div>
                @if ($active)
                    <button class="btn btn-sm btn-outline-primary" type="button" data-guest-editor-toggle>
                        Sửa thông tin
                    </button>
                @endif
            </header>
            <div class="dining-guest-card__details">
                <div><small>Số điện thoại</small><strong>{{ $diningSession->customer?->phone ?: 'Chưa có' }}</strong>
                </div>
                <div><small>Số khách</small><strong>{{ $diningSession->guest_count }} khách</strong></div>
                <div><small>Bàn</small><strong>{{ $diningSession->table->code }}</strong></div>
            </div>
            @if ($active)
                <form class="dining-guest-editor" method="post"
                    action="{{ route($operationPrefix . '.dining-sessions.update', $diningSession) }}"
                    data-guest-editor hidden>@csrf @method('put')
                    <div><label><span>Họ và tên</span><input class="form-control" name="customer_name"
                                value="{{ $diningSession->customer?->name }}"></label><label><span>Số điện
                                thoại</span><input class="form-control" name="phone"
                                value="{{ $diningSession->customer?->phone }}"></label><label><span>Số
                                khách</span><input class="form-control" type="number" min="1" max="100"
                                name="guest_count" value="{{ $diningSession->guest_count }}" required></label></div>
                    <input type="hidden" name="note" value="{{ $diningSession->note }}">
                    <footer><button class="btn btn-primary">Lưu thông tin</button><button
                            class="btn btn-outline-secondary" type="button" data-guest-editor-toggle>Hủy</button>
                    </footer>
                </form>
            @endif
        </section>
        <section class="dining-check-card"><span>Tạm tính món</span><strong>{{ number_format($itemsTotal) }} ₫</strong>
            <div>
                <p><b>{{ $billableItems->sum('quantity') }}</b> món</p>
                <p><b>{{ $diningSession->orders->count() }}</b> lượt gọi</p>
            </div><small>{{ $diningSession->bill ? 'Thanh toán đã được mở' : 'Chưa mở thanh toán' }}</small>
        </section>
    </div>

    <div class="dining-service-meta"><span>Nhân viên mở bàn: <b>{{ $diningSession->openedBy->name }}</b></span>
        @if ($diningSession->reservation)
            <span>Đặt bàn: <x-display-code :code="$diningSession->reservation->reservation_code" /></span>
            @endif @if ($diningSession->note)
                <span>Ghi chú: <b>{{ $diningSession->note }}</b></span>
            @endif
    </div>
    <section class="dining-order-board">
        <header class="dining-order-board__header">
            <div>
                <h2>Đơn tại bàn</h2>
                <p>Theo dõi món đã gọi, ghi chú khẩu vị và trạng thái bếp.</p>
            </div>
            @if ($active)
                @can('order.create')
                    <button class="btn btn-primary" type="button" data-add-order-toggle>+ Thêm món</button>
                @endcan
            @endif
        </header>
        @if ($active) @can('order.create')
            <section class="dining-add-order" data-add-order hidden>
                <div class="dining-add-order__top">
                    <div>
                        <h3>Gọi thêm món</h3>
                        <p>Bấm dấu cộng để đưa món vào danh sách chờ, sau đó gửi từng món xuống bếp.</p>
                    </div><button class="btn btn-link" type="button" data-add-order-toggle>Đóng</button>
                </div>
                <div class="dining-add-order__search"><input class="form-control" data-order-product-search
                        placeholder="Tìm theo tên món hoặc danh mục" autocomplete="off"></div>
                <div class="dining-add-order__layout">
                    <div class="dining-add-order__products">
                        @foreach ($products as $product)
                            <button type="button" data-order-product
                                data-search="{{ mb_strtolower($product->name . ' ' . $product->category->name) }}"
                                data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                data-price="{{ $product->price }}"
                                data-image="{{ $product->primary_image_url }}"><span>
                                    @if ($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="">@else🍽
                                    @endif
                                </span>
                                <div>
                                    <strong>{{ $product->name }}</strong><small>{{ $product->category->name }}</small>
                                </div><b>{{ number_format($product->price) }} ₫</b><i aria-hidden="true">+</i>
                            </button>
                        @endforeach
                    </div>
                    <aside class="dining-order-cart">
                        <h4>Món chờ gửi <span data-order-selected-count>0</span></h4>
                        <div data-order-cart>
                            <p data-order-cart-empty>Bấm dấu + ở món để thêm vào đây.</p>
                        </div>
                    </aside>
                </div>
            </section>
        @endcan
    @endif

    <div class="dining-order-rounds">
        @forelse($diningSession->orders as $order)
            <article class="dining-ticket">
                <header>
                    <div>
                        <strong>{{ str_starts_with($order->order_code, 'FUL-') || str_starts_with($order->order_code, 'NQ-') ? 'Món đặt trước' : 'Lượt gọi món' }}</strong><small><x-display-code
                                :code="$order->order_code" /> · {{ $order->ordered_at->format('H:i d/m/Y') }}</small>
                    </div>
                    <b>{{ number_format($order->items->reject(fn($item) => $item->status === \App\Enums\OrderItemStatus::Cancelled)->sum('line_total')) }}
                        ₫</b>
                </header>
                <div class="dining-ticket__items">
                    @foreach ($order->items as $item)
                        <div
                            class="dining-ticket-item {{ $item->status === \App\Enums\OrderItemStatus::Cancelled ? 'is-cancelled' : '' }}">
                            <div class="dining-item-product">
                                @if ($item->product?->primary_image_url)
                                    <img src="{{ $item->product->primary_image_url }}"
                                    alt="">@else<span>🍽</span>
                                @endif
                                <div>
                                    <strong>{{ $item->product_name }}</strong><small>{{ $item->note ?: 'Không có ghi chú' }}</small>
                                </div>
                            </div>
                            <div class="dining-ticket-item__quantity"><small>Số lượng</small><strong>×
                                    {{ $item->quantity }}</strong></div>
                            <div class="dining-ticket-item__status">
                                @if ($item->status === \App\Enums\OrderItemStatus::Cancelled)
                                <span class="status-badge text-bg-secondary">Đã hủy</span>@else<span
                                        class="status-badge text-bg-success">Đã gửi bếp</span>
                                @endif
                            </div><strong class="dining-ticket-item__money">{{ number_format($item->line_total) }}
                                ₫</strong>
                            @if (
                                $active &&
                                    in_array($item->status, [\App\Enums\OrderItemStatus::Waiting, \App\Enums\OrderItemStatus::Preparing], true))
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    data-item-edit-toggle="{{ $item->id }}">Sửa</button>
                            @endif
                        </div>
                        @if (
                            $active &&
                                in_array($item->status, [\App\Enums\OrderItemStatus::Waiting, \App\Enums\OrderItemStatus::Preparing], true))
                            <div class="dining-ticket-editor" data-item-editor="{{ $item->id }}" hidden>
                                @if ($item->status === \App\Enums\OrderItemStatus::Waiting)
                                    <form method="post"
                                        action="{{ route($operationPrefix . '.order-items.update', $item) }}">@csrf
                                        @method('patch')<label><span>Số lượng</span><input class="form-control"
                                                type="number" min="1" max="1000" name="quantity"
                                                value="{{ $item->quantity }}" required></label><label
                                            class="grow"><span>Ghi chú khẩu vị</span><input class="form-control"
                                                name="note" value="{{ $item->note }}"
                                                placeholder="Ít cay, không hành..."></label><button
                                            class="btn btn-primary">Lưu món</button></form>
                                @endif
                                @can($item->status === \App\Enums\OrderItemStatus::Waiting ?
                                    'order-item.cancel-waiting' : 'order-item.cancel-preparing')
                                    @php
                                        $cancelAction =
                                            $item->status === \App\Enums\OrderItemStatus::Waiting
                                                ? 'cancel-waiting'
                                                : 'cancel-preparing';
                                    @endphp
                                    <form method="post"
                                        action="{{ route($operationPrefix . '.order-items.' . $cancelAction, $item) }}">
                                        @csrf @method('patch')<label class="grow"><span>Khách hủy món — nhập lý
                                                do</span><input class="form-control" name="cancellation_reason"
                                                placeholder="Khách đổi ý, gọi nhầm món..." required></label><button
                                            class="btn btn-outline-danger">Hủy món</button></form>
                                @endcan
                            </div>
                        @endif
                    @endforeach
                </div>
            </article>
        @empty<div class="admin-empty-state">Bàn chưa gọi món. Bấm “+ Thêm món” để tạo lượt gọi đầu tiên.</div>
        @endforelse
    </div>
</section>

<script>
    document.querySelectorAll('[data-guest-editor-toggle]').forEach(button => button.addEventListener('click', () => {
        const editor = document.querySelector('[data-guest-editor]');
        editor.hidden = !editor.hidden
    }));
    document.querySelectorAll('[data-item-edit-toggle]').forEach(button => button.addEventListener('click', () => {
        const editor = document.querySelector(`[data-item-editor="${button.dataset.itemEditToggle}"]`);
        editor.hidden = !editor.hidden
    }));
    document.querySelectorAll('[data-add-order-toggle]').forEach(button => button.addEventListener('click', () => {
        const panel = document.querySelector('[data-add-order]');
        panel.hidden = !panel.hidden;
        if (!panel.hidden) {
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            panel.querySelector('[data-order-product-search]')?.focus()
        }
    }));
    document.querySelector('[data-order-product-search]')?.addEventListener('input', event => document.querySelectorAll(
        '[data-order-product]').forEach(product => product.hidden = !product.dataset.search.includes(event
        .target.value.trim().toLocaleLowerCase())));
    const enhanceQuantity = input => {
        if (input.closest('.dining-quantity-stepper')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'dining-quantity-stepper';
        input.before(wrapper);
        wrapper.append(input);
        input.readOnly = true;
        const minus = document.createElement('button');
        minus.type = 'button';
        minus.textContent = '−';
        minus.setAttribute('aria-label', 'Giảm số lượng');
        const plus = document.createElement('button');
        plus.type = 'button';
        plus.textContent = '+';
        plus.setAttribute('aria-label', 'Tăng số lượng');
        wrapper.prepend(minus);
        wrapper.append(plus);
        minus.addEventListener('click', () => {
            const min = Number(input.min || 1);
            input.value = Math.max(min, Number(input.value || min) - 1)
        });
        plus.addEventListener('click', () => {
            const max = Number(input.max || 1000);
            input.value = Math.min(max, Number(input.value || 0) + 1)
        })
    };
    document.querySelectorAll('input[type="number"]').forEach(enhanceQuantity);
    const quantityRoot = document.querySelector('[data-order-cart]');
    if (quantityRoot) new MutationObserver(() => quantityRoot.querySelectorAll('input[type="number"]').forEach(
        enhanceQuantity)).observe(quantityRoot, {
        childList: true,
        subtree: true
    });
    const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    } [char]));
    const selected = new Map();
    const refreshCart = () => {
        const count = selected.size;
        document.querySelector('[data-order-selected-count]').textContent = count;
        document.querySelector('[data-order-cart-empty]').hidden = count > 0
    };
    document.querySelectorAll('[data-order-product]').forEach(product => product.addEventListener('click', () => {
        if (selected.has(product.dataset.id)) {
            document.querySelector(`[data-cart-product="${product.dataset.id}"] input[type=number]`)
                ?.stepUp();
            return
        }
        selected.set(product.dataset.id, true);
        const row = document.createElement('form');
        row.method = 'post';
        row.action = '{{ route($orderStoreRoute, $diningSession) }}';
        row.className = 'dining-order-cart-item';
        row.dataset.cartProduct = product.dataset.id;
        const image = product.dataset.image ? `<img src="${escapeHtml(product.dataset.image)}" alt="">` :
            '🍽';
        row.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="dining-item-product">
                <span>${image}</span>
                <div>
                    <strong>${escapeHtml(product.dataset.name)}</strong>
                    <small>${Number(product.dataset.price).toLocaleString('vi-VN')} ₫</small>
                </div>
            </div>
            <input type="hidden" name="items[0][product_id]" value="${escapeHtml(product.dataset.id)}">
            <label>
                <span>Số lượng</span>
                <input class="form-control" type="number" min="1" max="1000" name="items[0][quantity]" value="1" required>
            </label>
            <label class="grow">
                <span>Ghi chú món</span>
                <input class="form-control" name="items[0][note]" placeholder="Ít cay, không hành...">
            </label>
            <div class="dining-order-cart-item__actions">
                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-cart-item>×</button>
                <button class="btn btn-sm btn-primary" type="submit">Gửi bếp</button>
            </div>
        `;
        row.querySelector('[data-remove-cart-item]').addEventListener('click', () => {
            selected.delete(product.dataset.id);
            row.remove();
            refreshCart()
        });
        document.querySelector('[data-order-cart]').append(row);
        refreshCart()
    }));
</script>
</div>
@endsection
