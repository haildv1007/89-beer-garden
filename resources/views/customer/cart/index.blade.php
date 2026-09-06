@extends('layouts.customer')
@section('title', __('customer_order.cart'))
@section('content')
    <div class="cart-heading cart-heading--compact">
        <div><span class="eyebrow">{{ __('customer_order.cart_eyebrow') }}</span>
            <p>{{ __('customer_ui.cart_copy') }}</p>
        </div><a class="btn btn-outline-primary" href="{{ route('customer.menu.index') }}">＋
            {{ __('customer_ui.continue_menu') }}</a>
    </div>
    @if ($rows === [])
        <div class="empty-state cart-empty-state"><span class="empty-state-mark" aria-hidden="true">⌑</span>
            <h2>{{ __('customer_order.empty') }}</h2>
            <p>Khám phá thực đơn và chọn những món bạn muốn thưởng thức.</p><a class="btn btn-primary mt-2"
                href="{{ route('customer.menu.index') }}">{{ __('app.home.view_menu') }}</a>
        </div>
    @else
        <div class="cart-layout">
            <section class="cart-lines" aria-label="{{ __('customer_order.cart_items') }}">
                <header class="cart-lines-heading">
                    <div>
                        <h2>Món trong giỏ</h2><span>{{ collect($rows)->sum('quantity') }} phần</span>
                    </div>
                </header>
                @foreach ($rows as $row)
                    <article class="cart-line" data-cart-line="{{ $row['product_id'] }}">
                        <img class="cart-line-image"
                            src="{{ $row['image_url'] ?: asset('images/brand/grilled-feast.jpg') }}" alt="">
                        <div class="cart-line-main"><span
                                class="cart-line-category">{{ $row['category'] ?: __('customer_order.product') }}</span>
                            <h2>{{ $row['name'] }}</h2><span
                                class="cart-line-unit">{{ $row['price'] === null ? '—' : number_format($row['price'], 0, ',', '.') . ' ₫' }}</span>
                            @if (!$row['available'])
                                <span class="status-badge text-bg-danger">{{ __('customer_order.unavailable') }}</span>
                            @endif
                            <details class="cart-note" @if ($row['note']) open @endif>
                                <summary>{{ __('customer_order.item_note') }}@if ($row['note'])
                                        <span>· {{ Str::limit($row['note'], 28) }}</span>
                                    @endif
                                </summary>
                                <form class="cart-note-form js-submit-once" method="post"
                                    action="{{ route('customer.cart.items.update', $row['product_id']) }}">@csrf
                                    @method('patch')<input type="hidden" name="quantity"
                                        value="{{ $row['quantity'] }}"><input id="note-{{ $row['product_id'] }}"
                                        class="form-control" name="note" value="{{ $row['note'] }}"><button
                                        class="btn btn-outline-primary cart-save"><svg viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path d="M5 12.5 9.2 17 19 7" />
                                        </svg><span>{{ __('app.save') }}</span></button></form>
                            </details>
                        </div>
                        <form class="cart-quantity-form" method="post"
                            action="{{ route('customer.cart.items.update', $row['product_id']) }}">@csrf
                            @method('patch')<input type="hidden" name="note" value="{{ $row['note'] }}"><span
                                class="visually-hidden">{{ __('customer_order.quantity') }}</span>
                            <div class="cart-quantity" aria-label="{{ __('customer_order.quantity') }}"><button
                                    type="button" data-cart-quantity-minus aria-label="Giảm số lượng">−</button><input
                                    id="quantity-{{ $row['product_id'] }}" type="number" min="1" max="1000"
                                    name="quantity" value="{{ $row['quantity'] }}" readonly><button type="button"
                                    data-cart-quantity-plus aria-label="Tăng số lượng">+</button></div>
                        </form>
                        <div class="cart-line-end"><strong class="cart-line-total"
                                data-cart-line-total>{{ $row['line_total'] === null ? '—' : number_format($row['line_total'], 0, ',', '.') . ' ₫' }}</strong>
                            <form class="cart-remove-form" method="post"
                                action="{{ route('customer.cart.items.destroy', $row['product_id']) }}"
                                data-confirm="{{ __('customer_order.confirm_remove') }}">@csrf @method('delete')<button
                                    class="cart-remove" aria-label="{{ __('customer_order.remove') }}"><svg
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5" />
                                    </svg><span class="visually-hidden">{{ __('customer_order.remove') }}</span></button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="cart-checkout">
                <h2>{{ __('customer_order.order_summary') }}</h2>
                <form method="post" action="{{ route('customer.cart.fulfillment.select') }}" data-fulfillment-form>@csrf
                    <fieldset class="fulfillment-picker">
                        <legend>{{ __('customer_order.choose_fulfillment') }}</legend>
                        @foreach (['at_table', 'dine_in', 'pickup', 'delivery'] as $type)
                            <label @class([
                                'fulfillment-option',
                                'is-selected' => $summary['fulfillment_type'] === $type,
                            ])>
                                <input type="radio" name="fulfillment_type" value="{{ $type }}"
                                    @checked($summary['fulfillment_type'] === $type)>
                                <span>
                                    <strong>{{ __('customer_order.fulfillment.' . $type) }}</strong>
                                    <small>{{ __('customer_order.fulfillment.' . $type . '_short') }}</small>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                </form>

                <dl class="cart-totals">
                    <div>
                        <dt>{{ __('checkout.subtotal') }}</dt>
                        <dd data-cart-subtotal>{{ number_format($summary['subtotal'], 0, ',', '.') }} ₫</dd>
                    </div>
                    <div>
                        <dt>{{ __('checkout.discount') }}@if ($summary['voucher_code'])
                                <small>({{ $summary['voucher_code'] }})</small>
                            @endif
                        </dt>
                        <dd data-cart-discount>− {{ number_format($summary['discount'], 0, ',', '.') }} ₫</dd>
                    </div>
                    <div data-shipping-row @class(['d-none' => $summary['fulfillment_type'] !== 'delivery'])>
                        <dt>{{ __('customer_order.shipping_fee') }}</dt>
                        <dd data-cart-shipping>
                            {{ $summary['shipping_fee'] === null ? __('customer_order.calculated_later') : number_format($summary['shipping_fee'], 0, ',', '.') . ' ₫' }}
                        </dd>
                    </div>
                </dl>
                <div class="cart-grand-total"><span>{{ __('checkout.total') }}</span><strong
                        data-cart-total>{{ number_format($summary['total'], 0, ',', '.') }} ₫</strong></div>

                <div class="cart-voucher">
                    <h3>{{ __('checkout.code') }}</h3>
                    <form class="js-submit-once" method="post" action="{{ route('customer.cart.voucher.apply') }}">@csrf
                        <div class="input-group"><input class="form-control" name="voucher_code" maxlength="100" required
                                value="{{ old('voucher_code', $summary['voucher_code']) }}"
                                placeholder="{{ __('customer_order.voucher_placeholder') }}"><button
                                class="btn btn-outline-primary">{{ __('checkout.apply') }}</button></div>
                    </form>
                    @if ($summary['voucher_code'])
                        <form class="mt-2" method="post" action="{{ route('customer.cart.voucher.remove') }}">@csrf
                            @method('delete')<button class="btn btn-link px-0">{{ __('checkout.remove') }}</button></form>
                    @endif
                </div>

                <a data-dine-in-checkout @class([
                    'btn',
                    'btn-primary',
                    'cart-checkout-action',
                    'w-100',
                    'mt-2',
                    'd-none' => $summary['fulfillment_type'] !== 'dine_in',
                ])
                    href="{{ route('customer.reservations.create', ['preorder' => 1]) }}">{{ __('customer_order.continue_reservation') }}</a>
                <a data-pickup-checkout @class([
                    'btn',
                    'btn-primary',
                    'cart-checkout-action',
                    'w-100',
                    'mt-2',
                    'd-none' => $summary['fulfillment_type'] !== 'pickup',
                ])
                    href="{{ route('customer.cart.checkout') }}">{{ __('pickup_checkout.continue') }}</a>
                <a data-delivery-checkout @class([
                    'btn',
                    'btn-primary',
                    'cart-checkout-action',
                    'w-100',
                    'mt-2',
                    'd-none' => $summary['fulfillment_type'] !== 'delivery',
                ])
                    href="{{ route('customer.cart.delivery-checkout') }}">{{ __('delivery_checkout.continue') }}</a>
                <div data-at-table-checkout @class([
                    'at-table-handoff',
                    'd-none' => $summary['fulfillment_type'] !== 'at_table',
                ])><span aria-hidden="true">✓</span>
                    <div>
                        <strong>{{ __('customer_order.fulfillment.at_table_handoff') }}</strong><small>{{ __('customer_order.fulfillment.at_table_handoff_help') }}</small>
                    </div>
                </div>
                <p data-fulfillment-empty @class(['cart-next-note', 'd-none' => $summary['fulfillment_type']])>{{ __('customer_order.select_fulfillment_hint') }}
                </p>
                <form class="mt-3" method="post" action="{{ route('customer.cart.clear') }}"
                    data-confirm="{{ __('customer_order.confirm_clear') }}">@csrf @method('delete')<button
                        class="btn btn-outline-light w-100">{{ __('customer_order.clear') }}</button></form>
            </aside>
        </div>
    @endif
@endsection
