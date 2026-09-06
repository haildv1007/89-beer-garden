<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ \App\Support\DisplayCode::short($kitchenTicket->ticket_code) }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm
        }

        * {
            box-sizing: border-box
        }

        body {
            width: 72mm;
            margin: 0 auto;
            color: #000;
            font: 14px/1.35 Arial, sans-serif
        }

        .toolbar {
            display: flex;
            gap: 8px;
            margin: 12px 0;
            padding: 10px;
            background: #eee
        }

        .toolbar button {
            padding: 10px 14px;
            font-weight: 700
        }

        header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 9px
        }

        h1 {
            margin: 0;
            font-size: 22px
        }

        .type {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase
        }

        .danger {
            border: 3px solid #000;
            padding: 4px;
            margin: 6px 0
        }

        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin: 10px 0
        }

        .meta b {
            text-align: right
        }

        .items {
            border-block: 2px dashed #000
        }

        .item {
            padding: 9px 0;
            border-bottom: 1px dashed #000
        }

        .item:last-child {
            border: 0
        }

        .item strong {
            display: block;
            font-size: 17px
        }

        .item p {
            margin: 4px 0;
            font-weight: 700
        }

        .change {
            display: block;
            margin-top: 5px;
            padding: 5px;
            border: 2px solid #000;
            font-weight: 900
        }

        .note {
            margin: 10px 0;
            padding: 7px;
            border: 2px solid #000
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 12px
        }

        @media print {
            .toolbar {
                display: none
            }

            body {
                width: auto
            }
        }
    </style>
</head>

<body>
    <div class="toolbar"><button onclick="window.print()">In phiếu</button><button onclick="window.close()">Đóng</button>
    </div>
    @php
        $p = $kitchenTicket->payload;
    @endphp
    <header>
        <div class="type {{ $kitchenTicket->type !== 'order' ? 'danger' : '' }}">
            {{ ['order' => 'PHIẾU BẾP', 'adjustment' => 'PHIẾU ĐIỀU CHỈNH', 'cancellation' => 'PHIẾU HỦY MÓN'][$kitchenTicket->type] }}
        </div>
        <h1>{{ $p['location'] ?? '' }}</h1>
        <div>{{ \App\Support\DisplayCode::short($kitchenTicket->ticket_code) }}</div>
    </header>
    <div class="meta"><span>Giờ gửi</span><b>{{ $kitchenTicket->created_at->format('H:i d/m/Y') }}</b><span>Mã
            đơn</span><b>{{ \App\Support\DisplayCode::short($p['order_code'] ?? null) }}</b>
        @if ($p['table_code'] ?? null)
            <span>Mã bàn</span><b>{{ $p['table_code'] }}</b>
            @endif @if ($p['requested_for'] ?? null)
                <span>Hẹn
                    nhận</span><b>{{ \Illuminate\Support\Carbon::parse($p['requested_for'])->format('H:i d/m') }}</b>
            @endif
    </div>
    <section class="items">
        @foreach ($p['items'] ?? [] as $item)
            <div class="item"><strong>{{ $item['quantity'] }} × {{ $item['product_name'] }}</strong>
                @if ($item['note'] ?? null)
                    <p>GHI CHÚ: {{ $item['note'] }}</p>
                    @endif @if ($item['change'] ?? null)
                        <span class="change">{{ $item['change'] }}</span>
                    @endif
            </div>
        @endforeach
    </section>
    @if ($p['order_note'] ?? null)
        <div class="note"><b>GHI CHÚ LƯỢT GỌI</b><br>{{ $p['order_note'] }}</div>
    @endif
    <div class="footer">
        Người gửi: {{ $kitchenTicket->createdByEmployee?->name ?? ($p['employee_name'] ?? 'Hệ thống') }}
        <br>
        PHIẾU BẾP — KHÔNG PHẢI HÓA ĐƠN THANH TOÁN
    </div>
    @if (request()->boolean('autoprint'))
        <script>
            window.addEventListener('load', () => setTimeout(() => window.print(), 250));
        </script>
    @endif
    @if (request()->boolean('autoprint'))
        <script>
            window.addEventListener('load', () => {
                window.parent.postMessage({
                    type: 'kitchen-ticket-ready',
                    ticketId: {{ $kitchenTicket->id }}
                }, location.origin);
                setTimeout(() => window.print(), 250)
            });
        </script>
    @endif
</body>

</html>
