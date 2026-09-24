@extends('layouts.admin')

@section('title', 'Chuyên mục tin tức')

@section('content')
    <div class="post-admin-page">
        <header class="admin-section-heading">
            <div>
                <h1>Chuyên mục tin tức</h1>
                <p>{{ $categories->count() }} chuyên mục · Số thứ tự nhỏ hiển thị trước.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.posts.index') }}">Bài viết</a>
                <a class="btn btn-primary" href="{{ route('admin.post-categories.create') }}">Thêm chuyên mục</a>
            </div>
        </header>
        <div class="admin-data-shell post-admin-table-shell">
            <table class="table align-middle admin-data-table post-admin-table">
                <thead>
                    <tr>
                        <th>Thứ tự</th>
                        <th>Chuyên mục</th>
                        <th>Đường dẫn</th>
                        <th>Bài viết</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $item)
                        <tr>
                            <td><span class="admin-status-badge">{{ $item->sort_order }}</span></td>
                            <td><a class="post-title-link"
                                    href="{{ route('admin.post-categories.edit', $item) }}">{{ $item->name }}</a></td>
                            <td><code>{{ $item->slug }}</code></td>
                            <td><a href="{{ route('admin.posts.index', ['category' => $item->slug]) }}">{{ $counts[$item->slug] ?? 0 }}
                                    bài</a></td>
                            <td>
                                <div class="post-admin-actions">
                                    <a class="btn btn-outline-primary"
                                        href="{{ route('admin.post-categories.edit', $item) }}">Chỉnh sửa</a>
                                    <form method="post" action="{{ route('admin.post-categories.destroy', $item) }}"
                                        onsubmit="return confirm('Xóa chuyên mục này?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-outline-danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Chưa có chuyên mục. Thêm chuyên mục để bắt đầu viết
                                bài.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
