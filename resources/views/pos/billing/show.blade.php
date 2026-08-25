@extends('layouts.pos')
@section('title', $bill->bill_code)
@section('content')
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="d-flex justify-content-between align-items-center"><h1>{{ $bill->bill_code }}</h1>@if($bill->status === \App\Enums\BillStatus::Paid)<a class="btn btn-primary" href="{{ route('pos.bills.invoice', $bill) }}">{{ __('billing.invoice') }}</a>@endif</div>
<p>{{ __('billing.session') }}: <a href="{{ route('pos.dining-sessions.show', $bill->diningSession) }}">{{ $bill->diningSession->session_code }}</a> · {{ __('billing.table') }}: {{ $bill->diningSession->table->code }}</p>
@include('pos.billing._items')
<dl class="row"><dt class="col-sm-4">{{ __('billing.subtotal') }}</dt><dd class="col-sm-8">{{ number_format($bill->subtotal, 0, ',', '.') }} ₫</dd><dt class="col-sm-4">{{ __('billing.voucher') }}</dt><dd class="col-sm-8">{{ $bill->voucher?->code ?? '—' }}</dd><dt class="col-sm-4">{{ __('billing.discount') }}</dt><dd class="col-sm-8">{{ number_format($bill->discount_amount, 0, ',', '.') }} ₫</dd><dt class="col-sm-4">{{ __('billing.total') }}</dt><dd class="col-sm-8"><strong>{{ number_format($bill->total_amount, 0, ',', '.') }} ₫</strong></dd><dt class="col-sm-4">{{ __('billing.status') }}</dt><dd class="col-sm-8">{{ __('billing.statuses.'.$bill->status->value) }}</dd></dl>
@if($bill->status !== \App\Enums\BillStatus::Paid)
    <form class="mb-3" method="post" action="{{ route('pos.billing.open', $bill->diningSession) }}">@csrf<button class="btn btn-outline-secondary">{{ __('billing.refresh') }}</button></form>
    @can('voucher.apply')
        <form class="row g-2 mb-3" method="post" action="{{ route('pos.bills.voucher.apply', $bill) }}">@csrf<div class="col-md-6"><input class="form-control" name="voucher_code" placeholder="{{ __('billing.voucher_code') }}"></div><div class="col-auto"><button class="btn btn-outline-primary">{{ __('billing.apply_voucher') }}</button></div></form>
        @if($bill->voucher)<form class="mb-3" method="post" action="{{ route('pos.bills.voucher.remove', $bill) }}">@csrf @method('delete')<button class="btn btn-outline-danger">{{ __('billing.remove_voucher') }}</button></form>@endif
    @endcan
    @can('payment.complete')
        <div class="row g-4"><div class="col-lg-6"><h2 class="h4">{{ __('billing.complete_payment') }}</h2><form method="post" action="{{ route('pos.bills.payments.complete', $bill) }}">@csrf @include('pos.billing._payment-fields')<div class="form-check my-3"><input class="form-check-input" id="confirmed" type="checkbox" name="confirmed_received" value="1"><label class="form-check-label" for="confirmed">{{ __('billing.confirm_received') }}</label></div><button class="btn btn-success">{{ __('billing.complete_payment') }}</button></form></div>
        <div class="col-lg-6"><h2 class="h4">{{ __('billing.record_failure') }}</h2><form method="post" action="{{ route('pos.bills.payments.fail', $bill) }}">@csrf @include('pos.billing._payment-fields')<label class="form-label mt-2">{{ __('billing.failure_reason') }}</label><textarea class="form-control" required maxlength="2000" name="failure_reason"></textarea><button class="btn btn-outline-danger mt-3">{{ __('billing.record_failure') }}</button></form></div></div>
    @endcan
@endif
<h2 class="h4 mt-4">{{ __('billing.attempts') }}</h2><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('billing.payment_code') }}</th><th>{{ __('billing.method') }}</th><th>{{ __('billing.amount') }}</th><th>{{ __('billing.status') }}</th><th>{{ __('billing.processed_by') }}</th><th>{{ __('billing.time') }}</th></tr></thead><tbody>@forelse($bill->payments as $payment)<tr><td>{{ $payment->payment_code }}</td><td>{{ __('billing.methods.'.$payment->method) }}</td><td>{{ number_format($payment->amount, 0, ',', '.') }} ₫</td><td>{{ __('billing.payment_statuses.'.$payment->status->value) }}</td><td>{{ $payment->processedBy->name }}</td><td>{{ ($payment->paid_at ?? $payment->failed_at)?->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="6">{{ __('billing.no_attempts') }}</td></tr>@endforelse</tbody></table></div>
@endsection
