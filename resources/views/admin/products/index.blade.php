@extends('layouts.admin')
@section('title', __('app.products.title'))
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('app.products.title') }}</h1>
        @can('product.manage')
            @can('product.update-price')
                <a class="btn btn-primary" href="{{ route('admin.products.create') }}">{{ __('app.create') }}</a>
            @endcan
        @endcan
    </div>
    <form class="row g-2 mb-3" method="get"><div class="col-md-5"><input class="form-control" name="q" value="{{ $filters['search'] }}" placeholder="{{ __('app.search') }}"></div><div class="col-md-3"><select class="form-select" name="category"><option value="">{{ __('app.menu.all_categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="status"><option value="">{{ __('app.all_statuses') }}</option><option value="active" @selected($filters['status'] === 'active')>{{ __('app.statuses.active') }}</option><option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('app.statuses.inactive') }}</option></select></div><div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">{{ __('app.search') }}</button></div></form>
    @if ($products->isEmpty())<div class="alert alert-info">{{ __('app.products.empty') }}</div>@else<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>{{ __('app.fields.name') }}</th><th>{{ __('app.categories.title') }}</th><th>{{ __('app.fields.price') }}</th><th>{{ __('app.fields.status') }}</th><th>{{ __('app.fields.availability') }}</th><th></th></tr></thead><tbody>@foreach($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->category->name }}</td><td>{{ number_format($product->price, 0, ',', '.') }} ₫</td><td>@include('admin.partials.status-badge', ['active' => $product->status === 'active', 'label' => __('app.statuses.'.$product->status)])</td><td>@include('admin.partials.status-badge', ['active' => $product->is_available, 'label' => $product->is_available ? __('app.products.available') : __('app.products.unavailable')])</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.products.show', $product) }}">{{ __('app.view_details') }}</a></td></tr>@endforeach</tbody></table></div>{{ $products->links() }}@endif
@endsection
