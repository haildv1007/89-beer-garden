@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', __('reservation.internal.title'))
@section('content')
    @php
        $reservationRoute = $adminContext ?? false ? 'admin.reservations' : 'pos.reservations';
    @endphp
    <div class="ops-page">
        <header class="page-heading ops-page-heading">
            <div>
                <h1>{{ __('reservation.internal.title') }}</h1>
                <p>{{ __('reservation.internal.operations_lead') }}</p>
            </div><a class="btn btn-primary" href="{{ route($reservationRoute . '.create') }}">＋ Thêm đặt bàn</a>
        </header>
        <form class="filter-bar reservation-filter ops-filter" method="get">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4"><label class="form-label" for="q">{{ __('app.search') }}</label><input
                        class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="{{ __('reservation.internal.search_placeholder') }}"></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label"
                        for="status">{{ __('reservation.fields.status') }}</label><select class="form-select"
                        id="status" name="status">
                        <option value="">{{ __('reservation.internal.all_statuses') }}</option>
                        @foreach (\App\Enums\ReservationStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                                {{ __('reservation.statuses.' . $status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-lg-2"><label class="form-label"
                        for="date_from">{{ __('reservation.internal.date_from') }}</label><input class="form-control"
                        id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-sm-4 col-lg-2"><label class="form-label"
                        for="date_to">{{ __('reservation.internal.date_to') }}</label><input class="form-control"
                        id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-lg-2"><button class="btn btn-primary w-100">{{ __('app.search') }}</button></div>
            </div>
        </form>

        @if ($reservations->isEmpty())
            <div class="empty-state">{{ __('reservation.internal.empty') }}</div>
        @else
            <section class="data-panel reservation-panel ops-data-shell">
                <div class="table-responsive">
                    <table class="table align-middle data-table--responsive reservation-table ops-data-table">
                        <thead>
                            <tr>
                                <th>Thời gian tạo</th>
                                <th>{{ __('reservation.fields.code') }}</th>
                                <th>{{ __('reservation.fields.customer') }}</th>
                                <th>Thời gian đến</th>
                                <th>{{ __('reservation.fields.party_size') }}</th>
                                <th>{{ __('reservation.fields.status') }}</th>
                                <th>{{ __('reservation.fields.table') }}</th>
                                <th>Phiên</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservations as $reservation)
                                <tr id="reservation-row-{{ $reservation->id }}">
                                    <td data-label="Thời gian tạo">
                                        <time class="reservation-created"
                                            datetime="{{ $reservation->created_at?->toIso8601String() }}">
                                            {{ $reservation->created_at?->format('d/m/Y') }}
                                            <small>{{ $reservation->created_at?->format('H:i') }}</small>
                                        </time>
                                    </td>
                                    <td data-label="{{ __('reservation.fields.code') }}"><strong><x-display-code
                                                :code="$reservation->reservation_code" /></strong></td>
                                    <td data-label="{{ __('reservation.fields.customer') }}">
                                        <strong>{{ $reservation->customer->name }}</strong><br><small>{{ $reservation->customer->phone }}</small>
                                    </td>
                                    <td data-label="Thời gian đến">
                                        <strong>{{ $reservation->reservation_date->format('d/m/Y') }}</strong><br><small>{{ substr($reservation->reservation_time, 0, 5) }}</small>
                                    </td>
                                    <td data-label="{{ __('reservation.fields.party_size') }}">
                                        {{ $reservation->party_size }}</td>
                                    <td data-label="{{ __('reservation.fields.status') }}">@include('partials.reservation-status-badge', [
                                        'status' => $reservation->status,
                                    ])
                                    </td>
                                    <td data-label="{{ __('reservation.fields.table') }}">
                                        {{ $reservation->table?->name ?: '—' }}</td>
                                    <td data-label="Phiên">
                                        @if ($reservation->diningSession)
                                            <a class="reservation-session-link"
                                                href="{{ route($adminContext ?? false ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show', $reservation->diningSession) }}"><x-display-code
                                                :code="$reservation->diningSession->session_code" /></a>@else<span class="text-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button"
                                            data-bs-toggle="modal" data-bs-target="#reservation-{{ $reservation->id }}"
                                            data-detail-url="{{ route($reservationRoute . '.show', $reservation) }}">{{ __('app.view_details') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="mt-3">{{ $reservations->links() }}</div>

            @foreach ($reservations as $reservation)
                <div class="modal fade reservation-modal" id="reservation-{{ $reservation->id }}" tabindex="-1"
                    aria-labelledby="reservation-title-{{ $reservation->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div><span class="reservation-modal__eyebrow">Chi tiết đặt bàn</span>
                                    <h2 class="modal-title" id="reservation-title-{{ $reservation->id }}"><x-display-code
                                            :code="$reservation->reservation_code" /></h2>
                                </div><button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <div class="reservation-modal__summary">
                                    <div>
                                        <span>Khách hàng</span>
                                        <strong>{{ $reservation->customer->name }}</strong>
                                        <small>
                                            {{ $reservation->customer->phone }}
                                            {{ $reservation->customer->email ? ' · ' . $reservation->customer->email : '' }}
                                        </small>
                                    </div>
                                    <div><span>Thời gian
                                            đến</span><strong>{{ substr($reservation->reservation_time, 0, 5) }} ·
                                            {{ $reservation->reservation_date->format('d/m/Y') }}</strong><small>Tạo lúc
                                            {{ $reservation->created_at?->format('H:i · d/m/Y') }}</small></div>
                                    <div>
                                        <span>Số khách</span>
                                        <strong>{{ $reservation->party_size }} khách</strong>
                                        <small>
                                            {{ $reservation->table
                                                ? $reservation->table->name . ' · ' . $reservation->table->code
                                                : __('reservation.internal.not_assigned') }}
                                        </small>
                                    </div>
                                    <div><span>Trạng
                                            thái</span><strong>@include('partials.reservation-status-badge', [
                                                'status' => $reservation->status,
                                            ])</strong><small>{{ $reservation->diningSession ? 'Phiên ' . \App\Support\DisplayCode::short($reservation->diningSession->session_code) : 'Chưa mở phiên phục vụ' }}</small>
                                    </div>
                                </div>
                                <section class="reservation-modal__note"><span>Ghi chú của khách</span>
                                    <p>{{ $reservation->note ?: 'Không có ghi chú' }}</p>
                                </section>
                                @if ($reservation->preorder)
                                    <section class="reservation-preorder">
                                        <div class="reservation-preorder__head">
                                            <h3>Món đặt trước</h3>
                                            <strong>{{ number_format($reservation->preorder->total_amount) }} ₫</strong>
                                        </div>
                                        <div class="reservation-preorder__items">
                                            @foreach ($reservation->preorder->items as $item)
                                                <article class="reservation-preorder__item">
                                                    @if ($item->product?->primary_image_url)
                                                        <img src="{{ $item->product->primary_image_url }}"
                                                        alt="{{ $item->product_name }}">@else<div
                                                            class="reservation-preorder__placeholder" aria-hidden="true">🍽
                                                        </div>
                                                    @endif
                                                    <div><strong>{{ $item->product_name }}</strong><span>Số lượng:
                                                            {{ $item->quantity }} · {{ number_format($item->line_total) }}
                                                            ₫</span>
                                                        @if ($item->note)
                                                            <small>Ghi chú: {{ $item->note }}</small>
                                                        @endif
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    </section>
                                @else<div class="reservation-modal__empty">Khách không đặt món trước.</div>
                                @endif
                                @if (in_array(
                                        $reservation->status,
                                        [\App\Enums\ReservationStatus::Pending, \App\Enums\ReservationStatus::Confirmed],
                                        true))
                                    <div class="collapse reservation-editor"
                                        id="reservation-editor-{{ $reservation->id }}">
                                        <div class="reservation-editor__heading"><strong>Chỉnh sửa đặt
                                                bàn</strong><span>Thông tin chỉ được lưu, chưa gửi món xuống Bếp.</span>
                                        </div>
                                        <form method="post"
                                            action="{{ route($reservationRoute . '.update', $reservation) }}">@csrf
                                            @method('put')
                                            <div class="reservation-editor__grid">
                                                <label><span>Họ và tên</span><input class="form-control" name="name"
                                                        value="{{ $reservation->customer->name }}" required></label>
                                                <label><span>Số điện thoại</span><input class="form-control"
                                                        name="phone" value="{{ $reservation->customer->phone }}"
                                                        required></label>
                                                <label><span>Ngày đến</span><input class="form-control" type="date"
                                                        name="reservation_date"
                                                        value="{{ $reservation->reservation_date->format('Y-m-d') }}"
                                                        required></label>
                                                <label><span>Giờ đến</span><input class="form-control" type="time"
                                                        name="reservation_time"
                                                        value="{{ substr($reservation->reservation_time, 0, 5) }}"
                                                        required></label>
                                                <label><span>Số khách</span><input class="form-control" type="number"
                                                        name="party_size" min="1" max="100"
                                                        value="{{ $reservation->party_size }}" required></label>
                                                <label class="reservation-editor__note"><span>Ghi chú đặt bàn</span>
                                                    <textarea class="form-control" name="note" rows="2">{{ $reservation->note }}</textarea>
                                                </label>
                                            </div>
                                            <div class="reservation-editor__items" data-reservation-items>
                                                <div class="reservation-editor__items-head"><strong>Món đặt
                                                        trước</strong><button class="btn btn-sm btn-outline-primary"
                                                        type="button" data-add-reservation-item>＋ Thêm món</button></div>
                                                @foreach ($reservation->preorder?->items ?? [] as $index => $editItem)
                                                    <div class="reservation-editor__item">
                                                        <select class="form-select"
                                                            name="items[{{ $index }}][product_id]" required>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                    @selected($editItem->product_id === $product->id)>{{ $product->name }} ·
                                                                    {{ number_format($product->price) }} ₫</option>
                                                            @endforeach
                                                        </select>
                                                        <input class="form-control" type="number"
                                                            name="items[{{ $index }}][quantity]" min="1"
                                                            max="100" value="{{ $editItem->quantity }}"
                                                            aria-label="Số lượng">
                                                        <input class="form-control"
                                                            name="items[{{ $index }}][note]"
                                                            value="{{ $editItem->note }}" placeholder="Ghi chú món">
                                                        <button class="btn btn-outline-danger" type="button"
                                                            data-remove-reservation-item aria-label="Xóa món">×</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <template data-reservation-item-template>
                                                <div class="reservation-editor__item"><select class="form-select"
                                                        data-name="product_id" required>
                                                        <option value="">Chọn món…</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }} ·
                                                                {{ number_format($product->price) }} ₫</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="form-control" data-name="quantity" type="number"
                                                        min="1" max="100" value="1"
                                                        aria-label="Số lượng"><input class="form-control"
                                                        data-name="note" placeholder="Ghi chú món"><button
                                                        class="btn btn-outline-danger" type="button"
                                                        data-remove-reservation-item aria-label="Xóa món">×</button>
                                                </div>
                                            </template>
                                            <div class="reservation-editor__actions"><button class="btn btn-primary"
                                                    type="submit">Lưu thay đổi</button><button
                                                    class="btn btn-outline-secondary" type="reset"
                                                    data-cancel-reservation-edit data-bs-toggle="collapse"
                                                    data-bs-target="#reservation-editor-{{ $reservation->id }}">Hủy</button><span
                                                    class="reservation-editor__feedback" role="status"
                                                    aria-live="polite"></span></div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer reservation-modal__actions">
                                @if ($reservation->status === \App\Enums\ReservationStatus::Pending)
                                    <button class="btn btn-outline-primary" type="button" data-open-reservation-edit
                                        data-bs-toggle="collapse"
                                        data-bs-target="#reservation-editor-{{ $reservation->id }}" aria-expanded="false"
                                        aria-controls="reservation-editor-{{ $reservation->id }}">Chỉnh sửa</button>
                                    <form method="post"
                                        action="{{ route($reservationRoute . '.confirm', $reservation) }}">@csrf
                                        @method('patch')<button
                                            class="btn btn-success">{{ __('reservation.internal.confirm') }}</button>
                                    </form>
                                    <form method="post"
                                        action="{{ route($reservationRoute . '.reject', $reservation) }}">
                                        @csrf @method('patch')<button
                                            class="btn btn-outline-danger">{{ __('reservation.internal.reject') }}</button>
                                    </form>
                                @endif
                                @if ($reservation->status === \App\Enums\ReservationStatus::Confirmed)
                                    <button class="btn btn-outline-primary" type="button" data-open-reservation-edit
                                        data-bs-toggle="collapse"
                                        data-bs-target="#reservation-editor-{{ $reservation->id }}" aria-expanded="false"
                                        aria-controls="reservation-editor-{{ $reservation->id }}">Sửa thông tin &amp;
                                        món</button>
                                @endif
                                @if (
                                    $reservation->status === \App\Enums\ReservationStatus::Confirmed &&
                                        auth()->user()->can('reservation.manage') &&
                                        auth()->user()->can('dining-session.open') &&
                                        auth()->user()->can('table.operate'))
                                    @php
                                        $suitableTables = $availableTables->where(
                                            'capacity',
                                            '>=',
                                            $reservation->party_size,
                                        );
                                    @endphp
                                    <form class="reservation-checkin" method="post"
                                        action="{{ route($reservationRoute . '.check-in', $reservation) }}">@csrf<select
                                            class="form-select" name="table_id" required>
                                            <option value="">Chọn bàn để check-in</option>
                                            @foreach ($suitableTables as $table)
                                                <option value="{{ $table->id }}">{{ $table->name }} ·
                                                    {{ $table->capacity }}
                                                    khách{{ $table->location ? ' · ' . $table->location : '' }}</option>
                                            @endforeach
                                        </select><button class="btn btn-primary"
                                            @disabled($suitableTables->isEmpty())>{{ __('dining_session.check_in') }}</button>
                                    </form>
                                @endif
                                @if ($reservation->status === \App\Enums\ReservationStatus::Confirmed && auth()->user()->can('reservation.mark-no-show'))
                                    <form method="post"
                                        action="{{ route($reservationRoute . '.mark-no-show', $reservation) }}">@csrf
                                        @method('patch')<button
                                            class="btn btn-outline-secondary">{{ __('reservation.internal.mark_no_show') }}</button>
                                    </form>
                                @endif
                                @if ($reservation->diningSession)
                                    <a class="btn btn-primary"
                                        href="{{ route(($adminContext ?? false ? 'admin' : 'pos') . '.dining-sessions.show', $reservation->diningSession) }}">Sửa
                                        món tại phiên</a>
                                @endif
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <script>
                document.addEventListener('click', (event) => {
                    const openButton = event.target.closest('[data-open-reservation-edit]');
                    if (openButton) {
                        const target = document.querySelector(openButton.dataset.bsTarget ?? '');
                        if (target instanceof HTMLElement && !target.dataset.originalHtml) target.dataset.originalHtml =
                            target.innerHTML;
                        return;
                    }
                    const cancelButton = event.target.closest('[data-cancel-reservation-edit]');
                    if (cancelButton) {
                        const editor = cancelButton.closest('.reservation-editor');
                        if (editor instanceof HTMLElement && editor.dataset.originalHtml) editor.innerHTML = editor.dataset
                            .originalHtml;
                        return;
                    }
                    const addButton = event.target.closest('[data-add-reservation-item]');
                    if (addButton) {
                        const editor = addButton.closest('.reservation-editor');
                        const items = editor?.querySelector('[data-reservation-items]');
                        const template = editor?.querySelector('[data-reservation-item-template]');
                        if (!(items instanceof HTMLElement) || !(template instanceof HTMLTemplateElement)) return;
                        const fragment = template.content.cloneNode(true);
                        const index = Date.now();
                        fragment.querySelectorAll('[data-name]').forEach((input) => {
                            input.name = `items[${index}][${input.dataset.name}]`;
                            input.removeAttribute('data-name');
                        });
                        items.append(fragment);
                        items.querySelector('.reservation-editor__item:last-child select')?.focus();
                        return;
                    }
                    const removeButton = event.target.closest('[data-remove-reservation-item]');
                    if (removeButton) removeButton.closest('.reservation-editor__item')?.remove();
                });

                document.addEventListener('submit', async (event) => {
                    const form = event.target.closest('.reservation-editor form');
                    if (!(form instanceof HTMLFormElement)) return;
                    event.preventDefault();
                    const modal = form.closest('.reservation-modal');
                    const submit = form.querySelector('button[type="submit"]');
                    const feedback = form.querySelector('.reservation-editor__feedback');
                    if (!(modal instanceof HTMLElement) || !(submit instanceof HTMLButtonElement)) return;
                    submit.disabled = true;
                    submit.textContent = 'Đang lưu…';
                    if (feedback) feedback.textContent = '';
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? result
                            .message ?? 'Không thể cập nhật đặt bàn.');

                        const refreshed = await fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const documentCopy = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
                        const replacementModal = documentCopy.querySelector(`#${modal.id} .modal-content`);
                        const reservationId = modal.id.replace('reservation-', '');
                        const currentRow = document.querySelector(`#reservation-row-${reservationId}`);
                        const replacementRow = documentCopy.querySelector(`#reservation-row-${reservationId}`);
                        if (replacementModal) modal.querySelector('.modal-content')?.replaceWith(replacementModal);
                        if (currentRow && replacementRow) currentRow.replaceWith(replacementRow);
                        const body = modal.querySelector('.modal-body');
                        if (body) body.scrollTop = body.scrollHeight;
                    } catch (error) {
                        if (feedback) feedback.textContent = error.message;
                        submit.disabled = false;
                        submit.textContent = 'Lưu thay đổi';
                    }
                });
            </script>
        @endif
    </div>
@endsection
