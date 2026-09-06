@extends('layouts.admin')

@section('title', 'Tin tức')

@section('content')
    @php
        $categoryLabels = \App\Models\PostCategory::labels();
    @endphp
    <div class="post-admin-page">
        <header class="admin-section-heading">
            <div>
                <h1>Tin tức</h1>
                <p>Quản lý bài viết, sự kiện và câu chuyện được hiển thị trên website.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.post-categories.index') }}">Chuyên mục</a>
                <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Viết bài</a>
            </div>
        </header>

        <form class="post-admin-filters" method="get">
            <input class="form-control" name="q" value="{{ $search }}" placeholder="Tìm tiêu đề hoặc slug">
            <select class="form-select" name="category">
                <option value="">Tất cả chuyên mục</option>
                @foreach ($categoryLabels as $value => $label)
                    <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="published" @selected($status === 'published')>Đã xuất bản</option>
                <option value="draft" @selected($status === 'draft')>Bản nháp</option>
            </select>
            <button class="btn btn-primary">Tìm kiếm</button>
        </form>

        @if ($posts->isEmpty())
            <div class="admin-empty-state">
                <span>▱</span>
                <strong>Chưa có bài viết</strong>
                <p>Tạo bài đầu tiên để bắt đầu khu vực tin tức của nhà hàng.</p>
            </div>
        @else
            <div class="admin-data-shell post-admin-table-shell">
                <table class="table align-middle admin-data-table post-admin-table">
                    <thead>
                        <tr>
                            <th>Bài viết</th>
                            <th>Chuyên mục</th>
                            <th>Ngày đăng</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    <div class="post-admin-title-cell">
                                        <div class="post-admin-thumbnail">
                                            @if ($post->featured_image_url)
                                                <img src="{{ $post->featured_image_url }}" alt="">
                                            @else
                                                <span aria-hidden="true">▱</span>
                                            @endif
                                        </div>
                                        <div>
                                            <a class="post-title-link" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                                            <small>{{ $post->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $categoryLabels[$post->category] }}</td>
                                <td>
                                    <strong>{{ $post->published_at?->format('d/m/Y') ?: '—' }}</strong>
                                    @if ($post->published_at)
                                        <small>{{ $post->published_at->format('H:i') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($post->status === 'draft')
                                        <span class="admin-status-badge">Bản nháp</span>
                                    @elseif ($post->published_at?->isFuture())
                                        <span class="admin-status-badge is-warning">Đã lên lịch</span>
                                    @else
                                        <span class="admin-status-badge is-success">Đã xuất bản</span>
                                    @endif
                                    @if ($post->is_featured)
                                        <small class="post-featured-label">Nổi bật</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="post-admin-actions">
                                        @if ($post->status === 'published' && $post->published_at?->isPast())
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank"
                                                href="{{ route('customer.posts.show', $post) }}">Xem</a>
                                        @endif
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('admin.posts.edit', $post) }}">Chỉnh sửa</a>
                                        <form method="post" action="{{ route('admin.posts.destroy', $post) }}"
                                            onsubmit="return confirm('Xóa bài viết này?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
