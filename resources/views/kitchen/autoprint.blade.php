@extends('layouts.kitchen')
@section('title', 'In Tự Động')
@section('content')
    <section class="print-station" data-autoprint>
        <div class="print-station__signal"><i></i><span>IN TỰ ĐỘNG ĐANG BẬT</span></div>
        <h1>In Tự Động</h1>
        <p>Giữ trang này mở để nhận và in các phiếu bếp mới.</p>
        <div class="print-station__status"><span>Trạng thái</span><strong data-autoprint-status>Đang kiểm tra phiếu
                mới…</strong><small data-autoprint-detail>Hệ thống tự cập nhật liên tục</small></div>
        <a class="btn btn-outline-primary" href="{{ route('kitchen.home') }}">Quay lại Phiếu bếp</a>
        <iframe title="Phiếu đang in" data-print-frame></iframe>
    </section>
@endsection
@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-autoprint]');
            if (!root) return;
            const status = root.querySelector('[data-autoprint-status]');
            const detail = root.querySelector('[data-autoprint-detail]');
            const frame = root.querySelector('[data-print-frame]');
            let busy = false;
            let activeId = null;
            let watchdog = null;
            const token = '{{ csrf_token() }}';
            const finish = async id => {
                if (!busy || id !== activeId) return;
                clearTimeout(watchdog);
                try {
                    const response = await fetch(`{{ url('/kitchen/autoprint/tickets') }}/${id}/complete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            Accept: 'application/json'
                        }
                    });
                    if (!response.ok) throw new Error();
                    status.textContent = 'Đã gửi lệnh in';
                    detail.textContent = 'Đang kiểm tra phiếu tiếp theo'
                } catch (error) {
                    status.textContent = 'Chưa thể xác nhận lệnh in';
                    detail.textContent = 'Hệ thống sẽ thử lại'
                } finally {
                    frame.src = 'about:blank';
                    busy = false;
                    activeId = null;
                    setTimeout(poll, 1000)
                }
            };
            const poll = async () => {
                if (busy) return;
                try {
                    const response = await fetch('{{ route('kitchen.autoprint.next') }}', {
                        headers: {
                            Accept: 'application/json'
                        },
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error();
                    const raw = await response.json();
                    const ticket = raw?.data ?? raw;
                    if (!ticket || !ticket.ticket_id) {
                        status.textContent = 'Không có phiếu chờ in';
                        detail.textContent = 'In tự động vẫn đang bật';
                        return
                    }
                    busy = true;
                    activeId = Number(ticket.ticket_id);
                    status.textContent = `Đang in ${ticket.ticket_code||'phiếu bếp'}`;
                    detail.textContent = 'Đang chuyển phiếu tới máy in';
                    frame.src = ticket.print_url;
                    watchdog = setTimeout(() => finish(activeId), 30000)
                } catch (error) {
                    status.textContent = 'Không kết nối được';
                    detail.textContent = 'Hệ thống sẽ tự thử lại'
                }
            };
            window.addEventListener('message', event => {
                if (event.origin !== location.origin || event.data?.type !== 'kitchen-ticket-ready') return;
                const id = Number(event.data.ticketId);
                if (id !== activeId) return;
                setTimeout(() => finish(id), 800)
            });
            poll();
            setInterval(poll, 3000)
        })();
    </script>
@endpush
