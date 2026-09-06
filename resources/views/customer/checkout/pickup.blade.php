@extends('layouts.customer')
@section('title', __('pickup_checkout.title'))
@section('content')
    <div class="checkout-heading">
        <div class="checkout-heading-nav"><a href="{{ route('customer.cart.index') }}">←
                {{ __('pickup_checkout.back') }}</a><span class="eyebrow">{{ __('pickup_checkout.eyebrow') }}</span></div>
        <h1 class="page-title">{{ __('pickup_checkout.title') }}</h1>
    </div>
    <form class="checkout-layout js-submit-once" method="post" action="{{ route('customer.cart.checkout.store') }}">@csrf
        <section class="checkout-form">
            <header class="checkout-card-heading"><span>01</span>
                <div>
                    <h2>{{ __('pickup_checkout.contact') }}</h2>
                    <p>Thông tin để nhà hàng liên hệ xác nhận đơn.</p>
                </div>
            </header>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="customer_name">{{ __('pickup_checkout.name') }}
                        *</label><input class="form-control" id="customer_name" name="customer_name" required
                        maxlength="255" value="{{ old('customer_name', $customer?->name) }}"></div>
                <div class="col-md-6"><label class="form-label" for="phone">{{ __('pickup_checkout.phone') }}
                        *</label><input class="form-control" id="phone" name="phone" required maxlength="30"
                        value="{{ old('phone', $customer?->phone) }}"></div>
                <div class="col-md-6"><label class="form-label"
                        for="email">{{ __('pickup_checkout.email') }}</label><input class="form-control" type="email"
                        id="email" name="email" maxlength="255" value="{{ old('email', $customer?->email) }}"></div>
                <div class="col-md-6"><label class="form-label" for="requested_for">{{ __('pickup_checkout.time') }}
                        *</label><input class="form-control" type="datetime-local" id="requested_for" name="requested_for"
                        required min="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}"
                        max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" value="{{ old('requested_for') }}"></div>
                <div class="col-12"><label class="form-label" for="pickup_note">{{ __('pickup_checkout.note') }}</label>
                    <textarea class="form-control" id="pickup_note" name="note" rows="3" maxlength="2000">{{ old('note') }}</textarea>
                </div>
            </div>
            <div class="pickup-notice">{{ __('pickup_checkout.notice') }}</div>
        </section>
        <aside class="checkout-review">
            <div class="checkout-review-summary">
                <h2>{{ __('pickup_checkout.your_order') }}</h2>
                <div class="checkout-review-items">
                    @foreach ($rows as $row)
                        <div><span>{{ $row['name'] }} <small>×
                                    {{ $row['quantity'] }}</small></span><strong>{{ number_format($row['line_total'], 0, ',', '.') }}
                                ₫</strong></div>
                    @endforeach
                </div>
                <dl>
                    <div>
                        <dt>{{ __('checkout.subtotal') }}</dt>
                        <dd>{{ number_format($summary['subtotal'], 0, ',', '.') }} ₫</dd>
                    </div>
                    <div>
                        <dt>{{ __('checkout.discount') }}</dt>
                        <dd>− {{ number_format($summary['discount'], 0, ',', '.') }} ₫</dd>
                    </div>
                </dl>
                <div class="checkout-review-total">
                    <span>{{ __('checkout.total') }}</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}
                        ₫</strong>
                </div>
                @if ($summary['voucher_code'])
                    <p class="checkout-voucher-code">{{ __('checkout.voucher') }}: {{ $summary['voucher_code'] }}</p>
                @endif
            </div>
            <div class="checkout-order-options"><label class="form-label" for="pickup_payment">Phương thức thanh toán
                    *</label><select class="form-select" id="pickup_payment" name="payment_option" required>
                    <option value="pay_on_receipt">Thanh toán khi nhận hàng</option>
                    <option value="bank_transfer" @selected(old('payment_option') === 'bank_transfer')>Chuyển khoản ngân hàng</option>
                </select><button class="btn btn-primary btn-lg w-100">{{ __('pickup_checkout.place') }}</button></div>
        </aside>
    </form>
@endsection
