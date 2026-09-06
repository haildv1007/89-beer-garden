@extends('layouts.admin')
@section('title', __('inventory.title'))
@section('content')
    <div class="d-flex justify-content-between">
        <h1>{{ __('inventory.title') }}</h1><a class="btn btn-primary"
            href="{{ route('admin.inventory-items.create') }}">{{ __('inventory.create') }}</a>
    </div>
    <form class="row g-2 my-3">
        <div class="col-md-5"><input class="form-control" name="q" value="{{ $search }}"
                placeholder="{{ __('inventory.search') }}"></div>
        <div class="col-md-3"><select class="form-select" name="status">
                <option value="">{{ __('inventory.all_statuses') }}</option>
                <option value="active" @selected($status === 'active')>{{ __('inventory.statuses.active') }}</option>
                <option value="inactive" @selected($status === 'inactive')>{{ __('inventory.statuses.inactive') }}</option>
            </select></div>
        <div class="col-md-2 form-check align-content-center"><input class="form-check-input" type="checkbox" id="low-stock"
                name="low_stock" value="1" @checked($lowStock)><label class="form-check-label"
                for="low-stock">{{ __('inventory.low_stock_only') }}</label></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.search') }}</button></div>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('inventory.fields.sku') }}</th>
                    <th>{{ __('inventory.fields.name') }}</th>
                    <th>{{ __('inventory.fields.product') }}</th>
                    <th>{{ __('inventory.fields.current_stock') }}</th>
                    <th>{{ __('inventory.fields.minimum_stock') }}</th>
                    <th>{{ __('inventory.fields.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><a href="{{ route('admin.inventory-items.show', $item) }}">{{ $item->sku }}</a></td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->product?->name ?? '—' }}</td>
                        <td>{{ number_format($item->current_stock) }} {{ $item->unit }} @if ($item->current_stock <= $item->minimum_stock)
                                <span class="badge text-bg-warning">{{ __('inventory.low_stock') }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->minimum_stock) }}</td>
                        <td>{{ __('inventory.statuses.' . $item->status) }}</td>
                </tr>@empty<tr>
                        <td colspan="6">{{ __('inventory.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>{{ $items->links() }}
@endsection
