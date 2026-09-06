@extends('layouts.customer')
@section('title', 'Thanh toán chuyển khoản')
@section('content')
    <section class="bank-transfer-page">
        <header><span class="eyebrow">THANH TOÁN ĐƠN HÀNG</span>
            <h1 class="page-title">Quét mã để chuyển khoản</h1>
            <p>Vui lòng chuyển đúng số tiền và giữ nguyên nội dung bên dưới.</p>
        </header>
        @if ($bank)
            @php
                $qrPath = $bank['bank_id'] . '-' . $bank['account_number'] . '-compact2.png';
                $qrQuery = 'amount=' . $fulfillmentOrder->total_amount;
                $qrQuery .= '&addInfo=' . rawurlencode($transferContent);
                $qrQuery .= '&accountName=' . rawurlencode($bank['account_name']);
                $qrUrl = 'https://img.vietqr.io/image/' . $qrPath . '?' . $qrQuery;
            @endphp
            <div class="bank-transfer-layout">
                <div class="bank-transfer-qr"><img src="{{ $qrUrl }}" alt="Mã VietQR thanh toán đơn hàng"><small>Mở ứng
                        dụng ngân hàng và quét mã VietQR</small></div>
                <div class="bank-transfer-details"><span>Tổng thanh
                        toán</span><strong>{{ number_format($fulfillmentOrder->total_amount, 0, ',', '.') }} ₫</strong>
                    <dl>
                        <div>
                            <dt>Ngân hàng</dt>
                            <dd>{{ $bank['bank_id'] }}</dd>
                        </div>
                        <div>
                            <dt>Số tài khoản</dt>
                            <dd>{{ $bank['account_number'] }}</dd>
                        </div>
                        <div>
                            <dt>Chủ tài khoản</dt>
                            <dd>{{ $bank['account_name'] }}</dd>
                        </div>
                        <div>
                            <dt>Nội dung</dt>
                            <dd>{{ $transferContent }}</dd>
                        </div>
                    </dl>
                    <p>Đơn chỉ được xác nhận đã thanh toán sau khi nhà hàng kiểm tra tiền vào tài khoản.</p>
                </div>
            </div>
            <form method="post"
                action="{{ URL::temporarySignedRoute('customer.cart.external-payment.report', now()->addDay(), ['fulfillmentOrder' => $fulfillmentOrder]) }}">
                @csrf<button class="btn btn-primary w-100" type="submit">Tôi đã chuyển khoản</button></form>
        @else
            <div class="bank-transfer-unavailable">
                <h2>Chưa thể hiển thị mã chuyển khoản</h2>
                <p>Nhà hàng chưa hoàn tất cấu hình tài khoản nhận tiền. Vui lòng liên hệ nhà hàng hoặc chọn thanh toán khi
                    nhận.</p>
            </div>
        @endif
    </section>
@endsection
