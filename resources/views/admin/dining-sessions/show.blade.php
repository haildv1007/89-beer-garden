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
        <header class="admin-record-header dining-session-header">
            <div class="dining-session-header__identity">
                <h1>{{ $diningSession->table->name }}</h1>
                <p><x-display-code :code="$diningSession->session_code" /> · Bắt đầu {{ $diningSession->started_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="dining-session-header__actions"><span
                    class="status-badge text-bg-{{ $active ? 'warning' : ($diningSession->status->value === 'cancelled' ? 'danger' : 'success') }}">{{ __('dining_session.statuses.' . $diningSession->status->value) }}</span>
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                    data-bs-target="#session-timeline-modal">Nhật ký phiên</button>
                @if (! $active && $diningSession->bill?->status === \App\Enums\BillStatus::Paid)
                    @can('billing.view')
                        <a class="btn btn-primary"
                            href="{{ route($operationPrefix . '.bills.invoice', $diningSession->bill) }}">Xem hóa đơn</a>
                    @endcan
                @endif
                @if ($active)
                    @can('table.operate')
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#transfer-table-modal">Đổi bàn</button>
                        <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal"
                            data-bs-target="#cancel-session-modal">Hủy phiên</button>
                    @endcan
                    @can('billing.view')
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
    <div class="modal fade" id="session-timeline-modal" tabindex="-1" aria-labelledby="session-timeline-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content session-timeline-modal">
                <div class="modal-header">
                    <div>
                        <small>NHẬT KÝ PHIÊN</small>
                        <h2 class="modal-title fs-5" id="session-timeline-title">{{ $diningSession->session_code }} · {{ $diningSession->table->name }}</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="session-timeline-summary">
                        <span><small>Bắt đầu</small><b>{{ $diningSession->started_at->format('H:i d/m/Y') }}</b></span>
                        <span><small>Khách hàng</small><b>{{ $diningSession->customer?->name ?: 'Khách lẻ' }}</b></span>
                        <span><small>Sự kiện</small><b>{{ $sessionTimeline->count() }}</b></span>
                    </div>
                    <div class="session-timeline-filters" role="group" aria-label="Lọc nhật ký">
                        <button class="is-active" type="button" data-timeline-filter="all">Tất cả</button>
                        <button type="button" data-timeline-filter="reservation">Đặt bàn</button>
                        <button type="button" data-timeline-filter="session">Phiên</button>
                        <button type="button" data-timeline-filter="order">Món</button>
                        <button type="button" data-timeline-filter="kitchen">Bếp</button>
                        <button type="button" data-timeline-filter="payment">Thanh toán</button>
                    </div>
                    <div class="session-timeline" data-session-timeline>
                        @forelse ($sessionTimeline as $event)
                            <article class="session-timeline-event is-{{ $event['category'] }}" data-timeline-category="{{ $event['category'] }}">
                                <time datetime="{{ $event['at']->toIso8601String() }}">
                                    <b>{{ $event['at']->format('H:i') }}</b><span>{{ $event['at']->format('d/m/Y') }}</span>
                                </time>
                                <i aria-hidden="true"></i>
                                <div>
                                    <header><strong>{{ $event['title'] }}</strong>
                                        @if ($event['code'])<x-display-code :code="$event['code']" />@endif
                                    </header>
                                    @if ($event['description'])<p>{{ $event['description'] }}</p>@endif
                                    @if ($event['actor'])<small>Thực hiện bởi <b>{{ $event['actor'] }}</b></small>@endif
                                </div>
                            </article>
                        @empty
                            <p class="text-muted mb-0">Chưa có sự kiện nào trong phiên.</p>
                        @endforelse
                    </div>
                    <p class="session-timeline-empty" data-timeline-empty hidden>Không có sự kiện trong nhóm này.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button></div>
            </div>
        </div>
    </div>

    @if ($active)
        @can('table.operate')
            <div class="modal fade" id="transfer-table-modal" tabindex="-1" aria-labelledby="transfer-table-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="{{ route($operationPrefix . '.dining-sessions.transfer-table', $diningSession) }}">
                            @csrf @method('patch')
                            <div class="modal-header">
                                <div><small>ĐỔI BÀN</small><h2 class="modal-title fs-5" id="transfer-table-title">Từ {{ $diningSession->table->name }} sang bàn khác</h2></div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <p>Phiên phục vụ, món đã gọi và hóa đơn được giữ nguyên. Bàn cũ sẽ chuyển sang chờ dọn.</p>
                                <label class="form-label" for="transfer-table-id">Bàn mới</label>
                                <select class="form-select @error('table_id') is-invalid @enderror" id="transfer-table-id" name="table_id" required>
                                    <option value="">Chọn bàn trống đủ chỗ</option>
                                    @foreach ($transferTables as $table)
                                        <option value="{{ $table->id }}" @selected(old('table_id') == $table->id)>{{ $table->name }} · {{ $table->capacity }} khách{{ $table->location ? ' · '.$table->location : '' }}</option>
                                    @endforeach
                                </select>
                                @error('table_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if ($transferTables->isEmpty())<small class="text-danger d-block mt-2">Hiện không có bàn trống đủ chỗ.</small>@endif

                                <label class="form-label mt-3" for="transfer-table-reason">Lý do đổi bàn</label>
                                <textarea class="form-control @error('reason') is-invalid @enderror" id="transfer-table-reason" name="reason" rows="3" maxlength="500" required placeholder="Ví dụ: khách muốn chuyển vào trong">{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button class="btn btn-primary" @disabled($transferTables->isEmpty())>Xác nhận đổi bàn</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="cancel-session-modal" tabindex="-1" aria-labelledby="cancel-session-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="{{ route($operationPrefix . '.dining-sessions.cancel', $diningSession) }}">
                            @csrf @method('patch')
                            <div class="modal-header">
                                <div><small>HỦY PHIÊN PHỤC VỤ</small><h2 class="modal-title fs-5" id="cancel-session-title">Hủy phiên tại {{ $diningSession->table->name }}</h2></div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <p>Chỉ dùng khi khách đã vào bàn nhưng rời đi và phiên chưa phát sinh món hoặc hóa đơn. Bàn sẽ chuyển sang chờ dọn.</p>
                                <label class="form-label" for="session-cancellation-reason">Lý do hủy phiên</label>
                                <textarea class="form-control @error('cancellation_reason') is-invalid @enderror" id="session-cancellation-reason" name="cancellation_reason" rows="3" maxlength="500" required placeholder="Ví dụ: Khách đổi ý và rời quán">{{ old('cancellation_reason') }}</textarea>
                                @error('cancellation_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @error('dining_session')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button class="btn btn-danger">Xác nhận hủy phiên</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif
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
                        <p>Bấm dấu cộng để đưa món vào danh sách chờ, sau đó gửi từng món hoặc gửi tất cả xuống bếp.</p>
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
                                data-variants='@json($product->availableVariants->map->only(["id", "name", "price"]))'
                                data-image="{{ $product->primary_image_url }}"><span>
                                    @if ($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="">@else🍽
                                    @endif
                                </span>
                                <div>
                                    <strong>{{ $product->name }}</strong><small>{{ $product->category->name }}</small>
                                </div><b>{{ number_format($product->price) }} ₫</b><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" /></svg></i>
                            </button>
                        @endforeach
                    </div>
                    <aside class="dining-order-cart">
                        <h4>Món chờ gửi <span data-order-selected-count>0</span></h4>
                        <div class="dining-order-cart__bulk" data-order-bulk-actions hidden>
                            <button class="btn btn-outline-danger" type="button" data-order-clear-all>Xóa tất cả</button>
                            <button class="btn btn-primary" type="button" data-order-send-all>Gửi tất cả</button>
                        </div>
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
            @php
                $uncancelled = $order->items->reject(fn ($item) => $item->status === \App\Enums\OrderItemStatus::Cancelled);
                $canCancelRound = $uncancelled->isNotEmpty()
                    && $uncancelled->every(fn ($item) => in_array($item->status, [\App\Enums\OrderItemStatus::Waiting, \App\Enums\OrderItemStatus::Preparing], true))
                    && (! $uncancelled->contains(fn ($item) => $item->status === \App\Enums\OrderItemStatus::Preparing) || auth()->user()->can('order-item.cancel-preparing'));
            @endphp
            <article class="dining-ticket">
                <header>
                    <div>
                        <strong>{{ str_starts_with($order->order_code, 'FUL-') || str_starts_with($order->order_code, 'NQ-') ? 'Món đặt trước' : 'Lượt gọi món' }}</strong><small><x-display-code
                                :code="$order->order_code" /> · {{ $order->ordered_at->format('H:i d/m/Y') }}</small>
                    </div>
                    <div class="dining-ticket__header-actions">
                        <b>{{ number_format($uncancelled->sum('line_total')) }} ₫</b>
                        @if ($active && $canCancelRound)
                            @can('order-item.cancel-waiting')
                                <button class="dining-ticket__cancel-toggle" type="button"
                                    data-round-cancel-toggle="{{ $order->id }}" aria-label="Hủy lượt gọi món"
                                    title="Hủy lượt gọi món" aria-expanded="false" aria-controls="round-cancel-{{ $order->id }}">
                                    <span aria-hidden="true">×</span>
                                </button>
                            @endcan
                        @endif
                    </div>
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
                                    <strong>{{ $item->product_name }}@if($item->variant_name) — {{ $item->variant_name }}@endif</strong><small>{{ $item->note ?: 'Không có ghi chú' }}</small>
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
                @if ($active && $canCancelRound)
                    @can('order-item.cancel-waiting')
                        <form id="round-cancel-{{ $order->id }}" class="dining-ticket__cancel" method="post" hidden
                            action="{{ route($operationPrefix . '.orders.cancel', [$diningSession, $order]) }}">
                            @csrf @method('patch')
                            <label><span>Lý do hủy cả lượt</span><input class="form-control" name="cancellation_reason"
                                    maxlength="1000" placeholder="Khách đổi ý, gọi nhầm lượt..." required></label>
                            <button class="btn btn-outline-danger" type="submit"
                                onclick="return confirm('Hủy toàn bộ món trong lượt này và gửi phiếu hủy cho bếp?')">Xác nhận hủy</button>
                        </form>
                    @endcan
                @endif
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
    document.querySelectorAll('[data-round-cancel-toggle]').forEach(button => button.addEventListener('click', () => {
        const form = document.getElementById(button.getAttribute('aria-controls'));
        form.hidden = !form.hidden;
        button.setAttribute('aria-expanded', String(!form.hidden));
        if (!form.hidden) form.querySelector('[name="cancellation_reason"]').focus();
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
        document.querySelector('[data-order-cart-empty]').hidden = count > 0;
        document.querySelector('[data-order-bulk-actions]').hidden = count === 0
    };
    document.querySelector('[data-order-clear-all]')?.addEventListener('click', () => {
        selected.clear();
        quantityRoot.querySelectorAll('[data-cart-product]').forEach(row => row.remove());
        refreshCart()
    });
    document.querySelector('[data-order-send-all]')?.addEventListener('click', event => {
        const rows = [...quantityRoot.querySelectorAll('[data-cart-product]')];
        if (rows.length === 0 || rows.some(row => !row.reportValidity())) return;
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '{{ route($orderStoreRoute, $diningSession) }}';
        form.hidden = true;
        const addField = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.append(input)
        };
        addField('_token', '{{ csrf_token() }}');
        rows.forEach((row, index) => {
            addField(`items[${index}][product_id]`, row.dataset.cartProduct);
            const variant = row.querySelector('[name$="[variant_id]"]');
            if (variant) addField(`items[${index}][variant_id]`, variant.value);
            addField(`items[${index}][quantity]`, row.querySelector('input[type="number"]').value);
            addField(`items[${index}][note]`, row.querySelector('input[name$="[note]"]').value)
        });
        document.body.append(form);
        event.currentTarget.disabled = true;
        form.requestSubmit()
    });
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
        const variants = JSON.parse(product.dataset.variants || '[]');
        const variantSelect = variants.length ? `<label class="dining-order-cart-item__variant"><span>Chọn set</span><select class="form-select" name="items[0][variant_id]" required>${variants.map(item => `<option value="${item.id}">${escapeHtml(item.name)} · ${Number(item.price).toLocaleString('vi-VN')} ₫</option>`).join('')}</select></label>` : '';
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
            ${variantSelect}
            <label class="dining-order-cart-item__quantity">
                <span>Số lượng</span>
                <input class="form-control" type="number" min="1" max="1000" name="items[0][quantity]" value="1" required>
            </label>
            <label class="grow dining-order-cart-item__note">
                <span>Ghi chú món</span>
                <input class="form-control" name="items[0][note]" placeholder="Ít cay, không hành...">
            </label>
            <div class="dining-order-cart-item__actions">
                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-cart-item aria-label="Xóa món" title="Xóa món">×</button>
                <button class="btn btn-sm btn-primary" type="submit" aria-label="Gửi bếp" title="Gửi bếp"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2 11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
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
