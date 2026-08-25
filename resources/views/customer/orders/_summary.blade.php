<div class="row g-3 mb-4">
    @foreach(['sessions','orders','spending','last_used_at'] as $metric)
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ __('order_history.overview.'.$metric) }}</div><strong>{{ $metric === 'spending' ? number_format($overview[$metric]).' đ' : ($overview[$metric] ?: '—') }}</strong></div></div></div>
    @endforeach
</div>
