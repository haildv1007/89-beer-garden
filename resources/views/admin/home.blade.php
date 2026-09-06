@extends('layouts.admin')

@section('title', __('app.contexts.admin') . ' — ' . __('app.name'))

@section('content')
    <x-admin.page-header :title="__('app.contexts.admin')" eyebrow="89 Beer Garden" :description="__('app.foundation_ready')" />
    <div class="admin-dashboard-grid">
        @can('restaurant-table.manage')
            <a
                href="{{ route('admin.restaurant-tables.index') }}"><span>▦</span><strong>{{ __('table.title') }}</strong><small>Operations</small></a>
        @endcan
        @can('product.manage')
            <a
                href="{{ route('admin.products.index') }}"><span>◇</span><strong>{{ __('app.products.title') }}</strong><small>Menu</small></a>
        @endcan
        @if (config('features.inventory'))
            @can('inventory.view')
                <a
                    href="{{ route('admin.inventory-items.index') }}"><span>▤</span><strong>{{ __('inventory.title') }}</strong><small>Business</small></a>
            @endcan
        @endif
        @can('report.view')
            <a
                href="{{ route('admin.reports.index') }}"><span>↗</span><strong>{{ __('report.title') }}</strong><small>Business</small></a>
        @endcan
    </div>
@endsection
