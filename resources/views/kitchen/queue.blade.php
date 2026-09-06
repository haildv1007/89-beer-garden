@extends('layouts.kitchen')
@section('title', 'Phiếu bếp')
@section('content')
    <header class="kds-toolbar">
        <div><span class="kds-eyebrow">Nhật ký và kiểm soát lệnh in</span>
            <h1>Phiếu bếp</h1>
            <p>Phiếu được chuyển tự động từ POS tới máy in bếp. Trang này dùng để kiểm tra hàng đợi và in lại khi có sự cố.
            </p>
        </div>
        <div class="kds-toolbar__actions">
            <a class="btn btn-primary" href="{{ route('kitchen.autoprint') }}" target="_blank">Mở In Tự Động</a>
            <a class="btn btn-warning" href="{{ route('kitchen.home') }}">↻ Làm mới</a>
        </div>
    </header>
    <section class="kds-summary" aria-label="Tổng quan phiếu bếp">
        <div><span>Hôm nay</span><strong>{{ $summary['today'] }}</strong></div>
        <div class="is-pending"><span>Chờ in</span><strong>{{ $summary['pending'] }}</strong></div>
        <div class="is-printed"><span>Đã in</span><strong>{{ $summary['printed'] }}</strong></div>
        <div class="is-failed"><span>In lỗi</span><strong>{{ $summary['failed'] }}</strong></div>
    </section>
    <form class="kds-filters" method="get"><label><span>Tìm phiếu, bàn hoặc món</span><input class="form-control"
                name="q" value="{{ $search }}"
                placeholder="Bàn 14, KOT-..., Bia Lager"></label><label><span>Loại phiếu</span><select class="form-select"
                name="type">
                <option value="">Tất cả</option>
                <option value="order" @selected(($filters['type'] ?? '') === 'order')>Gọi món</option>
                <option value="adjustment" @selected(($filters['type'] ?? '') === 'adjustment')>Điều chỉnh</option>
                <option value="cancellation" @selected(($filters['type'] ?? '') === 'cancellation')>Hủy món</option>
            </select></label><label><span>Trạng thái in</span><select class="form-select" name="status">
                <option value="">Tất cả</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ in tự động</option>
                <option value="printing" @selected(($filters['status'] ?? '') === 'printing')>Đang gửi máy in</option>
                <option value="printed" @selected(($filters['status'] ?? '') === 'printed')>Đã in</option>
                <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>In lỗi</option>
            </select></label><button class="btn btn-primary">Tìm kiếm</button></form>
    <section class="ticket-list">
        @forelse($tickets as $ticket)
            @php
                $payload = $ticket->payload;
                $ticketType = [
                    'order' => 'Gọi món',
                    'adjustment' => 'Điều chỉnh',
                    'cancellation' => 'Hủy món',
                ][$ticket->type];
            @endphp
            <article class="ticket-card ticket-card--{{ $ticket->type }}">
                <div class="ticket-card__identity">
                    <span class="ticket-type">{{ $ticketType }}</span>
                    <strong>{{ $payload['location'] ?? 'Không xác định' }}</strong>
                    <x-display-code :code="$ticket->ticket_code" />
                    <small title="{{ $payload['order_code'] ?? '' }}">
                        {{ \App\Support\DisplayCode::short($payload['order_code'] ?? null) }}
                    </small>
                    <small class="visually-hidden">{{ $payload['session_code'] ?? '' }}</small>
                    <small>{{ $ticket->created_at->format('H:i · d/m/Y') }}</small>
                </div>
                <div class="ticket-card__items">
                    @foreach ($payload['items'] ?? [] as $item)
                        <div><b>×{{ $item['quantity'] }} {{ $item['product_name'] }}</b>
                            @if ($item['note'] ?? null)
                                <span>Ghi chú: {{ $item['note'] }}</span>
                                @endif @if ($item['change'] ?? null)
                                    <em>{{ $item['change'] }}</em>
                                @endif
                        </div>
                        @endforeach @if ($payload['order_note'] ?? null)
                            <p><b>Ghi chú lượt gọi:</b> {{ $payload['order_note'] }}</p>
                        @endif
                </div>
                <div class="ticket-card__meta">
                    <span class="print-status print-status--{{ $ticket->status }}">
                        {{ ['pending' => 'Chờ in tự động', 'printing' => 'Đang gửi máy in', 'printed' => 'Đã in', 'failed' => 'In lỗi'][$ticket->status] }}
                    </span>
                    <small>{{ $ticket->createdByEmployee?->name ?? ($payload['employee_name'] ?? 'Hệ thống') }}</small>
                    @if ($ticket->printed_at)
                        <small>In gần nhất {{ $ticket->printed_at->format('H:i d/m') }} · {{ $ticket->print_attempts }}
                            lần</small>
                    @endif
                </div>
                <div class="ticket-card__actions"><a class="btn btn-outline-primary"
                        href="{{ route('kitchen.tickets.printable', $ticket) }}" target="_blank">Xem phiếu</a>
                    <form method="post" action="{{ route('kitchen.tickets.print', $ticket) }}" target="_blank">
                        @csrf
                        <button class="btn btn-primary">
                            {{ $ticket->status === 'printed' ? 'In lại phiếu' : 'In phiếu' }}
                            @if ($ticket->status !== 'printed')
                                <span class="visually-hidden">In thủ công</span>
                            @endif
                        </button>
                    </form>
                </div>
            </article>
        @empty<div class="kds-empty"><strong>Chưa có phiếu bếp phù hợp.</strong><span>Phiếu xuất hiện khi POS gửi món
                    hoặc xác nhận đơn ngoài quán.</span></div>
        @endforelse
    </section>
    <div class="kds-pagination">{{ $tickets->links() }}</div>
@endsection
@push('scripts')
    <script>
        setTimeout(() => {
            if (!document.hidden) location.reload()
        }, 15000)
    </script>
@endpush
