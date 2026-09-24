<div class="history-summary">
    <div><strong>{{ $overview['sessions'] }}</strong><span>Hóa đơn đã thanh toán</span></div>
    <div><strong>{{ number_format($overview['spending']) }} ₫</strong><span>Tổng chi tiêu</span></div>
    <div><strong>{{ $overview['last_used_at'] ? \Illuminate\Support\Carbon::parse($overview['last_used_at'])->format('d/m/Y') : '—' }}</strong><span>Thanh toán gần nhất</span></div>
</div>
