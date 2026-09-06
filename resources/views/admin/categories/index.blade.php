@extends('layouts.admin')
@section('title', __('app.categories.title'))
@section('content')
    <div class="catalog-page category-catalog-page">
        <header class="admin-section-heading category-page-heading">
            <div>
                <h1>{{ __('app.categories.title') }}</h1>
                <p>Sắp xếp và quản lý các nhóm món hiển thị trên thực đơn.</p>
            </div>
            @can('category.manage')
                <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">+ Tạo danh mục</a>
            @endcan
        </header>

        <form class="category-search" method="get">
            <input class="form-control" name="q" value="{{ $search }}"
                placeholder="Tìm theo tên hoặc slug danh mục">
            <button class="btn btn-primary">Tìm kiếm</button>
        </form>

        @if ($categories->isEmpty())
            <div class="admin-empty-state"><span>☰</span><strong>{{ __('app.categories.empty') }}</strong>
                <p>Tạo danh mục đầu tiên để bắt đầu xây dựng thực đơn.</p>
            </div>
        @else
            <div class="admin-data-shell category-table-shell">
                <table class="table align-middle admin-data-table category-table">
                    <thead>
                        <tr>
                            <th class="category-position">STT</th>
                            <th>Danh mục</th>
                            <th>Slug</th>
                            <th>Trạng thái</th>
                            <th>Sản phẩm</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="category-position"><strong>{{ $category->sort_order }}</strong></td>
                                <td><strong class="category-name">{{ $category->name }}</strong>
                                    @if ($category->description)
                                        <small>{{ \Illuminate\Support\Str::limit($category->description, 70) }}</small>
                                    @endif
                                </td>
                                <td><code class="category-slug">{{ $category->slug }}</code></td>
                                <td>@include('admin.partials.status-badge', [
                                    'active' => $category->status === 'active',
                                    'label' => __('app.statuses.' . $category->status),
                                ])</td>
                                <td><strong>{{ $category->products_count }}</strong><small>món</small></td>
                                <td>
                                    <div class="category-actions">
                                        @can('category.manage')
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('admin.categories.edit', $category) }}">Chỉnh sửa</a>
                                            @if ($category->products_count === 0)
                                                <form method="post"
                                                    action="{{ route('admin.categories.destroy', $category) }}"
                                                    onsubmit="return confirm('Xóa danh mục {{ addslashes($category->name) }}?')">
                                                    @csrf @method('delete')<button
                                                        class="btn btn-sm btn-outline-danger">Xóa</button></form>
                                            @else
                                                <button class="btn btn-sm btn-outline-danger" type="button"
                                                    data-delete-blocked-count="{{ $category->products_count }}">
                                                    Xóa
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $categories->links() }}</div>
        @endif
    </div>
    <script>
        document.querySelectorAll('[data-delete-blocked-count]').forEach((button) => {
            button.addEventListener('click', () => {
                const count = button.dataset.deleteBlockedCount;
                alert(
                    `Danh mục này đang có ${count} sản phẩm. ` +
                    'Hãy chuyển các sản phẩm sang danh mục khác trước khi xóa.',
                );
            });
        });
    </script>
@endsection
