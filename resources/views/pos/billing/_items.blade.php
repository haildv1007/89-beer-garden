@foreach($bill->diningSession->orders as $order)
    @if($order->items->isNotEmpty())
        <h3 class="h6 mt-3">{{ $order->order_code }} — {{ $order->ordered_at->format('d/m/Y H:i') }}</h3>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>{{ __('billing.product') }}</th><th>{{ __('billing.quantity') }}</th><th>{{ __('billing.unit_price') }}</th><th>{{ __('billing.line_total') }}</th><th>{{ __('billing.item_status') }}</th></tr></thead><tbody>
        @foreach($order->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price, 0, ',', '.') }} ₫</td><td>{{ number_format($item->line_total, 0, ',', '.') }} ₫</td><td>{{ __('order.statuses.'.$item->status->value) }}</td></tr>@endforeach
        </tbody></table></div>
    @endif
@endforeach
