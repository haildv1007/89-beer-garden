@extends('layouts.admin')
@section('title', __('report.title'))
@section('content')
<h1>{{ __('report.title') }}</h1>
<form class="row g-2 my-3" method="GET">
    <div class="col-md-2"><label class="form-label" for="preset">{{ __('report.period') }}</label><select class="form-select" id="preset" name="preset"><option value="today" @selected($preset === 'today')>{{ __('report.presets.today') }}</option><option value="7" @selected($preset === '7')>{{ __('report.presets.7') }}</option><option value="30" @selected($preset === '30')>{{ __('report.presets.30') }}</option><option value="custom" @selected($preset === 'custom')>{{ __('report.presets.custom') }}</option></select></div>
    <div class="col-md-3"><label class="form-label" for="from">{{ __('report.from') }}</label><input class="form-control" id="from" type="date" name="from" value="{{ $preset === 'custom' ? $from->toDateString() : '' }}"></div>
    <div class="col-md-3"><label class="form-label" for="to">{{ __('report.to') }}</label><input class="form-control" id="to" type="date" name="to" value="{{ $preset === 'custom' ? $to->toDateString() : '' }}"></div>
    <div class="col-md-2 align-content-end"><button class="btn btn-primary w-100">{{ __('report.apply') }}</button></div>
</form>
<p class="text-muted">{{ __('report.timezone_note', ['timezone' => config('app.timezone')]) }} · {{ $from->toDateString() }} — {{ $to->toDateString() }}</p>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('report.revenue') }}</div><div class="fs-2 fw-bold">{{ number_format($revenue) }} ₫</div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('report.valid_orders') }}</div><div class="fs-2 fw-bold">{{ number_format($validOrderCount) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('report.aov') }}</div><div class="fs-2 fw-bold">{{ number_format($averageOrderValue) }} ₫</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <section class="col-lg-7"><h2>{{ __('report.top_products') }}</h2><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('report.product') }}</th><th>{{ __('report.quantity') }}</th><th>{{ __('report.line_revenue') }}</th></tr></thead><tbody>@forelse($topProducts as $product)<tr><td>{{ $product->product_name }}</td><td>{{ number_format($product->quantity) }}</td><td>{{ number_format($product->line_revenue) }} ₫</td></tr>@empty<tr><td colspan="3">{{ __('report.empty') }}</td></tr>@endforelse</tbody></table></div></section>
    <section class="col-lg-5"><h2>{{ __('report.reservations') }}</h2><p class="text-muted">{{ __('report.reservation_basis') }}</p><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('report.status') }}</th><th>{{ __('report.count') }}</th></tr></thead><tbody>@forelse($reservationStats as $stat)<tr><td>{{ __('reservation.statuses.'.$stat->status) }}</td><td>{{ number_format($stat->total) }}</td></tr>@empty<tr><td colspan="2">{{ __('report.empty') }}</td></tr>@endforelse</tbody></table></div></section>
</div>

<section><h2>{{ __('report.revenue_details') }}</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ __('report.paid_at') }}</th><th>{{ __('report.payment') }}</th><th>{{ __('report.bill') }}</th><th>{{ __('report.session') }}</th><th>{{ __('report.table') }}</th><th>{{ __('report.subtotal') }}</th><th>{{ __('report.discount') }}</th><th>{{ __('report.total') }}</th><th>{{ __('report.method') }}</th><th>{{ __('report.employee') }}</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ \Illuminate\Support\Carbon::parse($payment->paid_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td><td>{{ $payment->payment_code }}</td><td>{{ $payment->bill_code }}</td><td>{{ $payment->session_code }}</td><td>{{ $payment->table_code }}</td><td>{{ number_format($payment->subtotal) }} ₫</td><td>{{ number_format($payment->discount_amount) }} ₫</td><td>{{ number_format($payment->total_amount) }} ₫</td><td>{{ __('report.methods.'.$payment->method) }}</td><td>{{ $payment->employee_name }}</td></tr>@empty<tr><td colspan="10">{{ __('report.empty') }}</td></tr>@endforelse</tbody></table></div>{{ $payments->links() }}</section>
@endsection
