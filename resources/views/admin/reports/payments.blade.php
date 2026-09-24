@extends('layouts.admin')
@section('title', 'Lịch sử thanh toán')
@section('content')
<div class="payment-report">
    @include('admin.reports._tabs')

    <div class="row g-3 mb-4 payment-summary">
        @foreach ([
            ['Đã thu', number_format($summary['total']).' ₫', $summary['count'].' giao dịch'],
            ['Tiền mặt', number_format($summary['cash']).' ₫', 'Đã xác nhận'],
            ['Chuyển khoản', number_format($summary['bank']).' ₫', 'SePay và xác nhận tay'],
            ['Cần đối soát', number_format($summary['review']), 'Giao dịch cần kiểm tra'],
        ] as [$label, $value, $note])
            <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">{{ $label }}</div>
                <div class="fs-3 fw-bold text-success my-1">{{ $value }}</div>
                <div class="small text-muted">{{ $note }}</div>
            </div></div></div>
        @endforeach
    </div>

    <div class="card mb-3 payment-filter-card">
        <div class="card-body">
            <form method="GET" class="payment-report-filters">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-lg-3"><label class="form-label" for="q">Tìm giao dịch</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Mã thanh toán, hóa đơn, khách..."></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="preset">Thời gian</label><select class="form-select" id="preset" name="preset">
                    <option value="today" @selected($preset === 'today')>Hôm nay</option><option value="7" @selected($preset === '7')>7 ngày</option><option value="14" @selected($preset === '14')>14 ngày</option><option value="custom" @selected($preset === 'custom')>Tùy chọn</option>
                </select></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="source">Nguồn đơn</label><select class="form-select" id="source" name="source"><option value="">Tất cả</option><option value="dine_in" @selected(request('source') === 'dine_in')>Tại bàn</option><option value="pickup" @selected(request('source') === 'pickup')>Nhận tại quán</option><option value="delivery" @selected(request('source') === 'delivery')>Giao tận nơi</option></select></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="method">Phương thức</label><select class="form-select" id="method" name="method"><option value="">Tất cả</option><option value="cash" @selected(request('method') === 'cash')>Tiền mặt</option><option value="bank_transfer" @selected(request('method') === 'bank_transfer')>Chuyển khoản</option><option value="other" @selected(request('method') === 'other')>Khác</option></select></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="employee_id">Người thu</label><select class="form-select" id="employee_id" name="employee_id"><option value="">Tất cả</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
                <div class="col-lg-1"><button class="btn btn-primary w-100">Lọc</button></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="from">Từ ngày</label><input class="form-control" id="from" type="date" name="from" value="{{ $preset === 'custom' ? $from->toDateString() : '' }}" @disabled($preset !== 'custom')></div>
                <div class="col-sm-6 col-lg-2"><label class="form-label" for="to">Đến ngày</label><input class="form-control" id="to" type="date" name="to" value="{{ $preset === 'custom' ? $to->toDateString() : '' }}" @disabled($preset !== 'custom')></div>
            </form>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 payment-list-tabs">
        <a @class(['btn', 'btn-primary' => $tab === 'history', 'btn-outline-primary' => $tab !== 'history']) href="{{ route('admin.reports.payments', array_merge(request()->except(['tab', 'page', 'status']), ['tab' => 'history'])) }}">Tất cả giao dịch</a>
        <a @class(['btn', 'btn-primary' => $tab === 'reconciliation', 'btn-outline-primary' => $tab !== 'reconciliation']) href="{{ route('admin.reports.payments', array_merge(request()->except(['tab', 'page', 'status']), ['tab' => 'reconciliation'])) }}">Cần đối soát <span class="badge text-bg-warning ms-1">{{ $summary['review'] }}</span></a>
    </div>

    <div class="card payment-history-table">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Thời gian</th><th>Giao dịch / chứng từ</th><th>Đơn / phiên</th><th class="text-center">Nguồn</th><th>Khách hàng</th><th>Phương thức</th><th class="text-end">Số tiền</th><th>Trạng thái</th><th>Thực hiện</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $sourceLabels = ['dine_in' => 'Tại bàn', 'pickup' => 'Nhận tại quán', 'delivery' => 'Giao tận nơi'];
                        $methodLabels = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'other' => 'Khác'];
                        $statusLabels = ['success'=>'Thành công','failed'=>'Thất bại','cancelled'=>'Đã hủy','expired'=>'Quá hạn','amount_mismatch'=>'Lệch số tiền','ignored'=>'Không khớp','already_paid'=>'Đơn đã thanh toán','order_rejected'=>'Đơn đã từ chối','invalid_payment_method'=>'Sai phương thức','before_order'=>'Giao dịch quá sớm','missing_operator'=>'Thiếu người xử lý','processing'=>'Đang xử lý'];
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ optional($row->occurred_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                        <td><div class="fw-semibold">{{ $row->transaction_code }}</div>@if($row->url)<a class="small" href="{{ $row->url }}">{{ $row->document_code }}</a>@else<span class="small text-muted">{{ $row->document_code }}</span>@endif @if($row->bank_reference)<div class="small text-muted">Mã NH: {{ $row->bank_reference }}</div>@endif</td>
                        <td>@if($row->order_session_url)<a class="fw-semibold text-nowrap" href="{{ $row->order_session_url }}">{{ $row->order_session_code }}</a>@else<span class="text-muted">Chưa xác định</span>@endif</td>
                        <td class="text-center"><span class="badge text-bg-light">{{ $sourceLabels[$row->source] ?? $row->source }}</span><div class="small text-muted mt-1">{{ $row->context }}</div></td>
                        <td>{{ $row->customer }}</td>
                        <td>{{ $methodLabels[$row->method] ?? $row->method }}</td>
                        <td class="text-end fw-bold text-nowrap">{{ number_format($row->amount) }} ₫</td>
                        <td><span @class(['badge', 'text-bg-success' => $row->status === 'success', 'text-bg-danger' => in_array($row->status, ['failed','amount_mismatch','expired']), 'text-bg-warning' => !in_array($row->status, ['success','failed','amount_mismatch','expired'])])>{{ $statusLabels[$row->status] ?? $row->status }}</span></td>
                        <td>{{ $row->actor }}</td>
                    </tr>
                @empty<tr><td colspan="9" class="text-center text-muted py-5">Không có giao dịch phù hợp.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
    </div>
    <script>
        const paymentPreset = document.querySelector('.payment-report-filters #preset');
        paymentPreset?.addEventListener('change', () => {
            document.querySelectorAll('.payment-report-filters input[type="date"]').forEach(input => {
                input.disabled = paymentPreset.value !== 'custom';
            });
        });
    </script>
</div>
@endsection
