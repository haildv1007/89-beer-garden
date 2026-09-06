@extends('layouts.admin')
@section('title', __('app.products.title'))
@section('content')
    <div class="catalog-page product-catalog-page">
        <div class="admin-section-heading product-list-heading">
            <div>
                <h1>{{ __('app.products.title') }}</h1>
                <p>Quản lý món, giá bán, trạng thái phục vụ và hình ảnh hiển thị trên thực đơn.</p>
            </div>
            @can('product.manage') @can('product.update-price')
            <a class="btn btn-primary" href="{{ route('admin.products.create') }}">+ Tạo sản phẩm</a>
            @endcan @endcan
        </div>
        <form class="admin-list-filter product-list-filter" method="get">
            <input class="form-control" name="q" value="{{ $filters['search'] }}"
                placeholder="Tìm theo tên hoặc slug sản phẩm">
            <select class="form-select" name="category">
                <option value="">{{ __('app.menu.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select class="form-select" name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                <option value="active" @selected($filters['status'] === 'active')>{{ __('app.statuses.active') }}</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('app.statuses.inactive') }}</option>
            </select>
            <button class="btn btn-primary">{{ __('app.search') }}</button>
        </form>
        @if ($products->isEmpty())
            <div class="admin-empty-state"><strong>{{ __('app.products.empty') }}</strong><span>Thử thay đổi từ khóa hoặc
                    bộ lọc để tìm sản phẩm.</span></div>
        @else
            <div class="admin-data-shell product-list-shell">
                <table class="table table-hover align-middle admin-data-table product-list-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>{{ __('app.fields.name') }}</th>
                            <th>{{ __('app.categories.title') }}</th>
                            <th>{{ __('app.fields.price') }}</th>
                            <th>{{ __('app.fields.status') }}</th>
                            <th>{{ __('app.fields.availability') }}</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td class="product-row-number">{{ $products->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="admin-product-cell"><img
                                            src="{{ $product->primary_image_url ?: asset('images/brand/quan-89-logo.png') }}"
                                            alt="{{ $product->name }}">
                                        <div><strong>{{ $product->name }}</strong><small>{{ $product->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><strong>{{ $product->category->name }}</strong></td>
                                <td class="product-list-price">{{ number_format($product->price, 0, ',', '.') }} ₫</td>
                                <td>@include('admin.partials.status-badge', [
                                    'active' => $product->status === 'active',
                                    'label' => __('app.statuses.' . $product->status),
                                ])</td>
                                <td>@include('admin.partials.status-badge', [
                                    'active' => $product->is_available,
                                    'label' => $product->is_available
                                        ? __('app.products.available')
                                        : __('app.products.unavailable'),
                                ])</td>
                                <td>
                                    @can('product.manage')
                                        <div class="product-row-actions"><a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('admin.products.edit', $product) }}">Chỉnh sửa</a>
                                            <form method="post" action="{{ route('admin.products.destroy', $product) }}"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">@csrf
                                                @method('delete')<button class="btn btn-sm btn-outline-danger"
                                                    type="submit">Xóa</button></form>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-pagination">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
