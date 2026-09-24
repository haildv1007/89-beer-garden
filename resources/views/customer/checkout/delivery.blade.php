@extends('layouts.customer')
@section('title', __('delivery_checkout.title'))
@section('content')
    <div class="checkout-heading">
        <div class="checkout-heading-nav"><a href="{{ route('customer.cart.index') }}">←
                {{ __('delivery_checkout.back') }}</a><span class="eyebrow">{{ __('delivery_checkout.eyebrow') }}</span>
        </div>
        <h1 class="page-title">{{ __('delivery_checkout.title') }}</h1>
    </div>
    <form class="checkout-layout js-submit-once" method="post" action="{{ route('customer.cart.delivery-checkout.store') }}">
        @csrf
        <section class="checkout-form">
            <header class="checkout-card-heading"><span>01</span>
                <div>
                    <h2>{{ __('delivery_checkout.contact') }}</h2>
                    <p>Thông tin liên hệ và địa chỉ nhận món.</p>
                </div>
            </header>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="customer_name">{{ __('delivery_checkout.name') }}
                        *</label><input class="form-control" id="customer_name" name="customer_name" required
                        maxlength="255" value="{{ old('customer_name', $customer?->name) }}"></div>
                <div class="col-md-6"><label class="form-label" for="phone">{{ __('delivery_checkout.phone') }}
                        *</label><input class="form-control" id="phone" name="phone" required maxlength="30"
                        value="{{ old('phone', $customer?->phone) }}"></div>
                <div class="col-md-6"><label class="form-label"
                        for="email">{{ __('delivery_checkout.email') }}</label><input class="form-control"
                        type="email" id="email" name="email" maxlength="255"
                        value="{{ old('email', $customer?->email) }}"></div>
                <div class="col-md-6"><label class="form-label" for="requested_for">{{ __('delivery_checkout.time') }}
                        *</label><input class="form-control" type="datetime-local" id="requested_for" name="requested_for"
                        required min="{{ now()->addMinutes(30)->format('Y-m-d\TH:i') }}"
                        max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" value="{{ old('requested_for') }}"></div>
                <div class="col-12"><label class="form-label" for="delivery_address">{{ __('delivery_checkout.address') }}
                        *</label>
                    <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2" maxlength="1000" required>{{ old('delivery_address') }}</textarea>
                </div>
                <div class="col-12"><label class="form-label"
                        for="delivery_note">{{ __('delivery_checkout.note') }}</label>
                    <textarea class="form-control" id="delivery_note" name="note" rows="3" maxlength="2000">{{ old('note') }}</textarea>
                </div>
                <div class="pickup-notice">{{ __('delivery_checkout.notice') }}</div>
        </section>
        <aside class="checkout-review">
            <div class="checkout-review-summary">
                <h2>{{ __('delivery_checkout.your_order') }}</h2>
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
                    <div>
                        <dt>{{ __('customer_order.shipping_fee') }}</dt>
                        <dd>{{ number_format($summary['shipping_fee'], 0, ',', '.') }} ₫</dd>
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
            <div class="checkout-order-options"><label class="form-label" for="delivery_payment">Phương thức thanh toán
                    *</label><select class="form-select" id="delivery_payment" name="payment_option" required>
                    <option value="pay_on_receipt">Thanh toán khi nhận hàng</option>
                    <option value="bank_transfer" @selected(old('payment_option') === 'bank_transfer')>Chuyển khoản ngân hàng</option>
                </select><button class="btn btn-primary btn-lg w-100">{{ __('delivery_checkout.place') }}</button></div>
        </aside>
    </form>
@endsection
