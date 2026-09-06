@extends('layouts.customer')
@section('title', __('reservation.customer.make') . ' — ' . __('app.name'))
@section('content')
    <div class="form-card reservation-form-card">
        <div class="row g-0">
            <div class="col-lg-5">
                <aside class="form-aside">
                    <div class="form-aside-content"><span class="eyebrow">{{ __('customer_ui.reservation_eyebrow') }}</span>
                        <h1>{{ __('reservation.customer.make') }}</h1>
                        <p>{{ __('customer_ui.reservation_copy') }}</p>
                        <p class="small mb-0">{{ __('reservation.customer.no_table_selection') }}</p>
                    </div>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="form-body">
                    <p class="form-required-note">{{ __('customer_ui.required_note') }}</p>
                    <form class="js-submit-once" method="post" action="{{ route('customer.reservations.store') }}"
                        novalidate>@csrf<div class="row g-3">
                            <div class="col-md-6"><label class="form-label"
                                    for="name">{{ __('reservation.fields.name') }} *</label><input
                                    class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                    required autocomplete="name" value="{{ old('name', $customer?->name) }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6"><label class="form-label"
                                    for="phone">{{ __('reservation.fields.phone') }} *</label><input
                                    class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                    required autocomplete="tel" value="{{ old('phone', $customer?->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4"><label class="form-label"
                                    for="reservation_date">{{ __('reservation.fields.date') }} *</label><input
                                    class="form-control @error('reservation_date') is-invalid @enderror"
                                    id="reservation_date" type="date" name="reservation_date" required
                                    value="{{ old('reservation_date') }}">
                                @error('reservation_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4"><label class="form-label"
                                    for="reservation_time">{{ __('reservation.fields.time') }} *</label><input
                                    class="form-control @error('reservation_time') is-invalid @enderror"
                                    id="reservation_time" type="time" name="reservation_time" required
                                    value="{{ old('reservation_time') }}">
                                @error('reservation_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4"><label class="form-label"
                                    for="party_size">{{ __('reservation.fields.party_size') }} *</label><input
                                    class="form-control @error('party_size') is-invalid @enderror" id="party_size"
                                    type="number" min="1" name="party_size" required
                                    value="{{ old('party_size') }}">
                                @error('party_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12"><label class="form-label"
                                    for="note">{{ __('reservation.fields.note') }}</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note" rows="4">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @if ($summary)
                            <input type="hidden" name="with_preorder" value="1">
                            <section class="reservation-preorder">
                                <h2>{{ __('customer_order.preorder_summary') }}</h2>
                                @foreach ($rows as $row)
                                    <div>
                                        <span>{{ $row['name'] }} × {{ $row['quantity'] }}</span>
                                        <strong>{{ number_format($row['line_total'], 0, ',', '.') }} ₫</strong>
                                    </div>
                                @endforeach
                                <div class="reservation-preorder-total">
                                    <span>{{ __('checkout.total') }}</span>
                                    <strong>{{ number_format($summary['total'], 0, ',', '.') }} ₫</strong>
                                </div>
                            </section>
                        @endif
                        <button class="btn btn-primary btn-reservation btn-lg reservation-submit">
                            {{ $summary ? __('customer_order.submit_reservation_preorder') : __('reservation.customer.submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
