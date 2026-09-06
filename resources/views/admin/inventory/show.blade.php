@extends('layouts.admin')
@section('title', $item->sku)
@section('content')
    <div class="d-flex justify-content-between">
        <h1>{{ $item->sku }} — {{ $item->name }}</h1>
        <div><a class="btn btn-outline-primary"
                href="{{ route('admin.inventory-items.edit', $item) }}">{{ __('app.edit') }}</a>
            @can('inventory.stock-movement.create')
                <a class="btn btn-primary"
                    href="{{ route('admin.inventory-items.movements.create', $item) }}">{{ __('inventory.create_movement') }}</a>
            @endcan
        </div>
    </div>
    @if ($item->current_stock <= $item->minimum_stock)
        <div class="alert alert-warning">{{ __('inventory.low_stock_warning') }}</div>
    @endif
    <dl class="row">
        <dt class="col-sm-4">{{ __('inventory.fields.product') }}</dt>
        <dd class="col-sm-8">{{ $item->product?->name ?? '—' }}</dd>
        <dt class="col-sm-4">{{ __('inventory.fields.current_stock') }}</dt>
        <dd class="col-sm-8">{{ number_format($item->current_stock) }} {{ $item->unit }}</dd>
        <dt class="col-sm-4">{{ __('inventory.fields.minimum_stock') }}</dt>
        <dd class="col-sm-8">{{ number_format($item->minimum_stock) }} {{ $item->unit }}</dd>
        <dt class="col-sm-4">{{ __('inventory.fields.status') }}</dt>
        <dd class="col-sm-8">{{ __('inventory.statuses.' . $item->status) }}</dd>
    </dl>
    <h2>{{ __('inventory.history') }}</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('inventory.fields.time') }}</th>
                    <th>{{ __('inventory.fields.type') }}</th>
                    <th>{{ __('inventory.fields.quantity') }}</th>
                    <th>{{ __('inventory.fields.before') }}</th>
                    <th>{{ __('inventory.fields.after') }}</th>
                    <th>{{ __('inventory.fields.actor') }}</th>
                    <th>{{ __('inventory.fields.note') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ __('inventory.types.' . $movement->type->value) }}</td>
                        <td>{{ number_format($movement->quantity) }}</td>
                        <td>{{ number_format($movement->stock_before) }}</td>
                        <td>{{ number_format($movement->stock_after) }}</td>
                        <td>{{ $movement->createdBy->name }}</td>
                        <td>{{ $movement->note ?: '—' }}</td>
                </tr>@empty<tr>
                        <td colspan="7">{{ __('inventory.no_movements') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>{{ $movements->links() }}
@endsection
