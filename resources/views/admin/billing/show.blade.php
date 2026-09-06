@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', 'Thanh toán ' . $bill->bill_code)
@section('content')
    @php
        $paid = $bill->status === \App\Enums\BillStatus::Paid;
        $billingPrefix = $adminContext ?? false ? 'admin' : 'pos';
    @endphp
    <a class="admin-back-link" href="{{ route($billingPrefix . '.dining-sessions.show', $bill->diningSession) }}">
        ← Phiên {{ \App\Support\DisplayCode::short($bill->diningSession->session_code) }}</a>
    <header class="billing-page-header">
        <div><span>THANH TOÁN TẠI BÀN</span>
            <h1>{{ $bill->diningSession->table->name }}</h1>
            <p><x-display-code :code="$bill->bill_code" /> ·
                {{ $bill->diningSession->customer?->name ?? __('dining_session.anonymous') }}</p>
        </div>
        <div class="billing-header-actions"><span
                class="admin-status-badge {{ $paid ? 'is-success' : 'is-warning' }}">{{ $paid ? 'Đã thanh toán' : 'Chờ thanh toán' }}</span>
            @if ($paid)
                <a class="btn btn-primary" href="{{ route($billingPrefix . '.bills.invoice', $bill) }}">In hóa đơn</a>
            @endif
        </div>
    </header>

    <div class="billing-workspace">
        <main>
            <section class="billing-card billing-items-card">
                <header>
                    <div>
                        <h2>Món trong hóa đơn</h2>
                        <p>{{ $bill->diningSession->orders->sum(fn($order) => $order->items->sum('quantity')) }} món từ
                            {{ $bill->diningSession->orders->count() }} lượt gọi</p>
                    </div>
                    @unless ($paid)
                        <form method="post"
                            action="{{ route($adminContext ?? false ? 'admin.dining-sessions.billing.open' : 'pos.billing.open', $bill->diningSession) }}">
                            @csrf<button class="btn btn-sm btn-outline-primary">Cập nhật món</button></form>
                    @endunless
                </header>
                <div class="billing-items-table">
                    <div class="billing-items-columns"><span>Món</span><span>Số lượng</span><span>Đơn giá</span><span>Thành
                            tiền</span></div>
                    @foreach ($bill->diningSession->orders as $order)
                        @if ($order->items->isNotEmpty())
                            <article class="billing-order-group">
                                <div class="billing-order-heading">
                                    <div><strong>Lượt gọi
                                            {{ $loop->iteration }}</strong><small>{{ $order->ordered_at->format('H:i · d/m/Y') }}
                                            · {{ \App\Support\DisplayCode::short($order->order_code) }}</small></div>
                                    <strong>{{ number_format($order->items->sum('line_total'), 0, ',', '.') }} ₫</strong>
                                </div>
                                @foreach ($order->items as $item)
                                    <div class="billing-line">
                                        <div class="billing-line-product">
                                            <strong>{{ $item->product_name }}</strong><small>{{ __('order.statuses.' . $item->status->value) }}</small>
                                        </div><strong class="billing-line-quantity">×
                                            {{ $item->quantity }}</strong><span>{{ number_format($item->unit_price, 0, ',', '.') }}
                                            ₫</span><strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong>
                                    </div>
                                @endforeach
                            </article>
                        @endif
                    @endforeach
                </div>
            </section>

            @if ($bill->payments->isNotEmpty())
                <section class="billing-card billing-attempts">
                    <header>
                        <div>
                            <h2>Lịch sử thanh toán</h2>
                            <p>Các lần xử lý thanh toán của hóa đơn này.</p>
                        </div>
                    </header>
                    <div class="admin-data-shell">
                        <table class="table admin-data-table">
                            <thead>
                                <tr>
                                    <th>Mã giao dịch</th>
                                    <th>Phương thức</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Nhân viên</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bill->payments as $payment)
                                    <tr>
                                        <td><strong><x-display-code :code="$payment->payment_code" /></strong></td>
                                        <td>{{ __('billing.methods.' . $payment->method) }}</td>
                                        <td><strong>{{ number_format($payment->amount, 0, ',', '.') }} ₫</strong></td>
                                        @php
                                            $paymentStatusClass =
                                                $payment->status->value === 'success' ? 'is-success' : 'is-danger';
                                        @endphp
                                        <td>
                                            <span class="admin-status-badge {{ $paymentStatusClass }}">
                                                {{ __('billing.payment_statuses.' . $payment->status->value) }}
                                            </span>
                                        </td>
                                        <td>{{ $payment->processedBy->name }}</td>
                                        <td>{{ optional($payment->paid_at ?? $payment->failed_at)->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </main>

        <aside>
            <section class="billing-card billing-summary-card">
                <header>
                    <div>
                        <h2>Tổng thanh toán</h2>
                        <p>Số tiền được tính lại theo món hiện tại.</p>
                    </div>
                </header>
                <dl>
                    <div>
                        <dt>Tạm tính</dt>
                        <dd>{{ number_format($bill->subtotal, 0, ',', '.') }} ₫</dd>
                    </div>
                    <div>
                        <dt>Voucher</dt>
                        <dd>{{ $bill->voucher?->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Giảm giá</dt>
                        <dd class="discount">− {{ number_format($bill->discount_amount, 0, ',', '.') }} ₫</dd>
                    </div>
                    <div class="total">
                        <dt>Khách cần trả</dt>
                        <dd>{{ number_format($bill->total_amount, 0, ',', '.') }} ₫</dd>
                    </div>
                </dl>
            </section>

            @unless ($paid)
                @can('voucher.apply')
                    <section class="billing-card billing-voucher-card">
                        <header>
                            <div>
                                <h2>Voucher</h2>
                                <p>Áp dụng mã ưu đãi trước khi thu tiền.</p>
                            </div>
                        </header>
                        @if ($bill->voucher)
                            <div class="billing-applied-voucher">
                                <div><span>ĐANG ÁP DỤNG</span><strong>{{ $bill->voucher->code }}</strong></div>
                                <form method="post" action="{{ route($billingPrefix . '.bills.voucher.remove', $bill) }}">@csrf
                                    @method('delete')<button class="btn btn-sm btn-outline-danger">Gỡ mã</button></form>
                        </div>@else<form method="post" action="{{ route($billingPrefix . '.bills.voucher.apply', $bill) }}">
                                @csrf<label for="voucher_code">Mã voucher</label>
                                <div><input class="form-control @error('voucher_code') is-invalid @enderror" id="voucher_code"
                                        name="voucher_code" placeholder="Nhập mã ưu đãi" required><button
                                        class="btn btn-outline-primary">Áp dụng</button></div>
                                @error('voucher_code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </form>
                        @endif
                    </section>
                @endcan

                @can('payment.complete')
                    <section class="billing-card billing-payment-card">
                        <header>
                            <div>
                                <h2>Xác nhận thanh toán</h2>
                                <p>Chốt hóa đơn sẽ kết thúc phiên và chuyển bàn sang chờ dọn.</p>
                            </div>
                        </header>
                        <form method="post" action="{{ route($billingPrefix . '.bills.payments.complete', $bill) }}"
                            data-payment-form>@csrf<label for="method">Phương thức</label><select class="form-select"
                                id="method" name="method" data-payment-method>
                                <option value="cash" @selected(old('method', 'cash') === 'cash')>{{ __('billing.methods.cash') }}</option>
                                <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>
                                    {{ __('billing.methods.bank_transfer') }}</option>
                                <option value="other" @selected(old('method') === 'other')>{{ __('billing.methods.other') }}</option>
                            </select>
                            <div class="billing-cash-fields" data-cash-fields><label for="cash_received">Tiền khách
                                    đưa</label><input class="form-control @error('cash_received') is-invalid @enderror"
                                    id="cash_received" inputmode="numeric" min="0" name="cash_received" type="number"
                                    value="{{ old('cash_received', $bill->total_amount) }}" data-cash-received>
                                <div class="billing-change"><span>Tiền trả lại</span><strong data-cash-change>0 ₫</strong></div>
                                @error('cash_received')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <label class="billing-confirm-check"><input type="checkbox" name="confirmed_received" value="1"
                                    @checked(old('confirmed_received'))><span>Tôi xác nhận đã nhận đủ
                                    <strong>{{ number_format($bill->total_amount, 0, ',', '.') }} ₫</strong></span></label>
                            @error('confirmed_received')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror @error('payment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <button class="btn btn-primary w-100" data-payment-submit>Xác nhận thanh toán</button>
                        </form>
                        <details class="billing-failure">
                            <summary>Ghi nhận giao dịch thất bại</summary>
                            <form method="post" action="{{ route($billingPrefix . '.bills.payments.fail', $bill) }}">@csrf<select
                                    class="form-select" name="method">
                                    <option value="cash">{{ __('billing.methods.cash') }}</option>
                                    <option value="bank_transfer">{{ __('billing.methods.bank_transfer') }}</option>
                                    <option value="other">{{ __('billing.methods.other') }}</option>
                                </select>
                                <textarea class="form-control" required maxlength="2000" name="failure_reason" placeholder="Lý do thất bại"></textarea>
                                <button class="btn btn-outline-danger w-100">Lưu lần thất bại</button>
                            </form>
                        </details>
                    </section>
                @endcan
            @endunless
        </aside>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-payment-form]');
            if (!form) return;
            const method = form.querySelector('[data-payment-method]');
            const fields = form.querySelector('[data-cash-fields]');
            const received = form.querySelector('[data-cash-received]');
            const change = form.querySelector('[data-cash-change]');
            const total = {{ (int) $bill->total_amount }};
            const refresh = () => {
                const cash = method.value === 'cash';
                fields.hidden = !cash;
                received.disabled = !cash;
                const amount = Number.parseInt(received.value || '0', 10);
                change.textContent = new Intl.NumberFormat('vi-VN').format(Math.max(0, amount - total)) + ' ₫';
            };
            method.addEventListener('change', refresh);
            received.addEventListener('input', refresh);
            form.addEventListener('submit', () => {
                const button = form.querySelector('[data-payment-submit]');
                button.disabled = true;
                button.textContent = 'Đang xử lý...';
            });
            refresh();
        });
    </script>
@endsection
