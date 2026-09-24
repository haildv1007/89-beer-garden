@extends('layouts.customer')
@section('title', 'Thanh toán chuyển khoản')
@section('content')
    <section class="bank-transfer-page" data-payment-status-url="{{ URL::temporarySignedRoute('customer.cart.external-payment.status', now()->addDay(), ['fulfillmentOrder' => $fulfillmentOrder]) }}">
        <header><h1 class="page-title">Thanh toán đơn hàng</h1></header>
        @if ($bank)
            @php
                $paymentExpired = $fulfillmentOrder->payment_expires_at?->isPast() ?? false;
                $qrPath = $bank['bank_id'] . '-' . $bank['account_number'] . '-compact2.png';
                $qrQuery = 'amount=' . $fulfillmentOrder->total_amount;
                $qrQuery .= '&addInfo=' . rawurlencode($transferContent);
                $qrQuery .= '&accountName=' . rawurlencode($bank['account_name']);
                $qrUrl = 'https://img.vietqr.io/image/' . $qrPath . '?' . $qrQuery;
            @endphp
            <div class="bank-transfer-layout">
                <section class="bank-transfer-card bank-transfer-payment">
                    <header><span>02</span><div><h2>Thông tin thanh toán</h2><p>Quét mã hoặc chuyển khoản theo đúng thông tin.</p></div></header>
                    @unless($paymentExpired)<div class="bank-transfer-payment__body" data-payment-sensitive>
                        <div class="bank-transfer-qr"><img src="{{ $qrUrl }}" alt="Mã VietQR thanh toán đơn hàng"><small>Mở ứng dụng ngân hàng và quét mã VietQR</small></div>
                        <div class="bank-transfer-details"><span>Số tiền cần chuyển</span><strong>{{ number_format($fulfillmentOrder->total_amount, 0, ',', '.') }} ₫</strong>
                            <dl>
                                <div><dt>Ngân hàng</dt><dd>{{ $bank['bank_id'] }}</dd></div>
                                <div><dt>Số tài khoản</dt><dd>{{ $bank['account_number'] }}</dd></div>
                                <div><dt>Chủ tài khoản</dt><dd>{{ $bank['account_name'] }}</dd></div>
                                <div><dt>Nội dung chuyển khoản</dt><dd>{{ $transferContent }}</dd></div>
                            </dl>
                            <p>Đơn được xác nhận thanh toán sau khi nhà hàng kiểm tra tiền vào tài khoản.</p>
                        </div>
                    </div>@endunless
                    <div class="bank-transfer-live-status" data-payment-live-status
                        data-payment-expires-at="{{ $fulfillmentOrder->payment_expires_at?->getTimestampMs() }}">
                        <span aria-hidden="true"></span><strong>{{ $paymentExpired ? 'Mã thanh toán đã hết hạn' : 'Đang chờ thanh toán' }}</strong>
                        <small>Còn <b data-payment-countdown>{{ $paymentExpired ? '00:00' : '30:00' }}</b></small>
                    </div>
                </section>
                <aside class="bank-transfer-card bank-transfer-order">
                    <header><span>01</span><div><h2>Thông tin đơn hàng</h2><p>Kiểm tra lại trước khi chuyển khoản.</p></div></header>
                    <dl class="bank-transfer-order__meta">
                        <div><dt>Mã đơn</dt><dd>{{ \App\Support\DisplayCode::short($fulfillmentOrder->order_code) }}</dd></div>
                        <div><dt>Hình thức nhận</dt><dd>{{ $fulfillmentOrder->fulfillment_type === 'delivery' ? 'Giao tận nơi' : 'Nhận tại quán' }}</dd></div>
                        @if ($fulfillmentOrder->requested_for)<div><dt>Thời gian nhận</dt><dd>{{ $fulfillmentOrder->requested_for->format('H:i d/m/Y') }}</dd></div>@endif
                        <div><dt>Khách hàng</dt><dd>{{ $fulfillmentOrder->customer_name }}</dd></div>
                        <div><dt>Số điện thoại</dt><dd>{{ $fulfillmentOrder->phone }}</dd></div>
                        @if ($fulfillmentOrder->delivery_address)<div class="is-stacked"><dt>Địa chỉ giao</dt><dd>{{ $fulfillmentOrder->delivery_address }}</dd></div>@endif
                    </dl>
                    <div class="bank-transfer-order__items">
                        @foreach ($fulfillmentOrder->items as $item)
                            <div><span>{{ $item->quantity }} × {{ $item->product_name }}@if($item->variant_name) — {{ $item->variant_name }}@endif</span><strong>{{ number_format($item->line_total, 0, ',', '.') }} ₫</strong></div>
                        @endforeach
                    </div>
                    <dl class="bank-transfer-order__totals">
                        <div><dt>Tạm tính</dt><dd>{{ number_format($fulfillmentOrder->subtotal, 0, ',', '.') }} ₫</dd></div>
                        @if ($fulfillmentOrder->discount_amount > 0)<div><dt>Giảm giá</dt><dd>− {{ number_format($fulfillmentOrder->discount_amount, 0, ',', '.') }} ₫</dd></div>@endif
                        @if ($fulfillmentOrder->shipping_fee > 0)<div><dt>Phí giao hàng</dt><dd>{{ number_format($fulfillmentOrder->shipping_fee, 0, ',', '.') }} ₫</dd></div>@endif
                        <div class="is-total"><dt>Tổng thanh toán</dt><dd>{{ number_format($fulfillmentOrder->total_amount, 0, ',', '.') }} ₫</dd></div>
                    </dl>
                </aside>
            </div>
        @else
            <div class="bank-transfer-unavailable">
                <h2>Chưa thể hiển thị mã chuyển khoản</h2>
                <p>Nhà hàng chưa hoàn tất cấu hình tài khoản nhận tiền. Vui lòng liên hệ nhà hàng hoặc chọn thanh toán khi
                    nhận.</p>
            </div>
        @endif
    </section>
    @if ($bank)
        <script>
            (() => {
                const page = document.querySelector('[data-payment-status-url]');
                const status = document.querySelector('[data-payment-live-status]');
                const countdown = status.querySelector('[data-payment-countdown]');
                const sensitive = document.querySelector('[data-payment-sensitive]');
                const expiresAt = Number(status.dataset.paymentExpiresAt);
                let stopped = false;
                const updateCountdown = () => {
                    if (!expiresAt || stopped) return;
                    const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
                    const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const seconds = String(remaining % 60).padStart(2, '0');
                    countdown.textContent = `${minutes}:${seconds}`;
                    if (remaining === 0) {
                        stopped = true;
                        status.classList.add('is-expired');
                        status.querySelector('strong').textContent = 'Mã thanh toán đã hết hạn';
                        if (sensitive) sensitive.remove();
                    }
                };
                const check = async () => {
                    if (stopped || document.hidden) return;
                    try {
                        const response = await fetch(page.dataset.paymentStatusUrl, {headers: {Accept: 'application/json'}});
                        if (!response.ok) return;
                        const data = await response.json();
                        if (data.paid && data.redirect_url) {
                            stopped = true;
                            status.classList.add('is-paid');
                            status.querySelector('strong').textContent = 'Đã nhận thanh toán';
                            window.location.assign(data.redirect_url);
                        } else if (data.expired) {
                            stopped = true;
                            status.classList.add('is-expired');
                            status.querySelector('strong').textContent = 'Mã thanh toán đã hết hạn';
                            if (sensitive) sensitive.remove();
                        }
                    } catch (_) {}
                };
                check();
                updateCountdown();
                const countdownTimer = setInterval(() => stopped ? clearInterval(countdownTimer) : updateCountdown(), 1000);
                const timer = setInterval(() => stopped ? clearInterval(timer) : check(), 3000);
                document.addEventListener('visibilitychange', check);
            })();
        </script>
    @endif
@endsection
