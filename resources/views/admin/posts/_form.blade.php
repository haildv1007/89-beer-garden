@php
    $categoryLabels = \App\Models\PostCategory::labels();
@endphp

@csrf
<input type="hidden" name="content_format" value="html">
@if ($post->exists)
    @method('put')
@endif

<div class="post-editor-layout">
    <main>
        <section class="post-editor-card">
            <header>
                <h2>Nội dung bài viết</h2>
                <p>Tiêu đề và phần giới thiệu cần ngắn gọn, dễ đọc trên điện thoại.</p>
            </header>
            <div class="mb-3">
                <label class="form-label" for="title">Tiêu đề</label>
                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                    value="{{ old('title', $post->title) }}" maxlength="180" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug">Đường dẫn</label>
                <input class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                    value="{{ old('slug', $post->slug) }}" placeholder="Tự tạo từ tiêu đề nếu để trống">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="excerpt">Mô tả ngắn</label>
                <textarea class="form-control @error('excerpt') is-invalid @enderror" id="excerpt" name="excerpt" rows="3"
                    maxlength="500" required>{{ old('excerpt', $post->excerpt) }}</textarea>
                <div class="form-text">Hiển thị ở danh sách tin tức và kết quả tìm kiếm.</div>
                @error('excerpt')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </section>
        <section class="post-editor-card">
            <header>
                <h2>Soạn thảo nội dung</h2>
                <p>Định dạng bài viết, chèn ảnh, liên kết và bảng.</p>
            </header>
            <div>
                <label class="form-label" for="content">Nội dung</label>
                <textarea class="form-control post-content-input @error('content') is-invalid @enderror" id="content" name="content"
                    rows="18" data-post-editor data-upload-url="{{ route('admin.post-images.store') }}" required>{{ old('content', $post->content_html) }}</textarea>
                <div class="form-text">Chèn ảnh từ máy bằng nút Image · tối đa 5 MB/ảnh. Dùng tiêu đề 2, 3 để chia mục.
                </div>
                @error('content')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </section>
    </main>

    <aside>
        <section class="post-editor-card">
            <header>
                <h2>Xuất bản</h2>
            </header>
            <div class="mb-3">
                <label class="form-label" for="category">Chuyên mục</label>
                <select class="form-select" id="category" name="category" required>
                    @foreach ($categoryLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', $post->category ?: 'food') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <a class="form-text d-inline-block" href="{{ route('admin.post-categories.index') }}" target="_blank"
                    rel="noopener">Quản lý chuyên mục</a>
                @error('category')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Trạng thái</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="draft" @selected(old('status', $post->status ?: 'draft') === 'draft')>Bản nháp</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Xuất bản</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="published_at">Thời gian đăng</label>
                <input class="form-control" id="published_at" name="published_at" type="datetime-local"
                    value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                <div class="form-text">Để trống để đăng ngay khi chọn “Xuất bản”.</div>
            </div>
            <label class="post-feature-toggle">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))>
                <span><strong>Đánh dấu nổi bật</strong><small>Ưu tiên bài này ở đầu danh sách.</small></span>
            </label>
        </section>

        <section class="post-editor-card">
            <header>
                <h2>Ảnh đại diện</h2>
                <p>Tỷ lệ khuyến nghị 16:9, tối đa 5 MB.</p>
            </header>
            @if ($post->featured_image_url)
                <img class="post-editor-preview" src="{{ $post->featured_image_url }}" alt="">
                <label class="post-remove-image">
                    <input type="checkbox" name="remove_featured_image" value="1">
                    Xóa ảnh hiện tại
                </label>
            @endif
            <input class="form-control @error('featured_image') is-invalid @enderror" name="featured_image"
                type="file" accept="image/jpeg,image/png,image/webp">
            @error('featured_image')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </section>
    </aside>
</div>

<div class="post-editor-actions">
    <a class="btn btn-outline-secondary" href="{{ route('admin.posts.index') }}">Hủy</a>
    <button class="btn btn-primary">{{ $post->exists ? 'Lưu thay đổi' : 'Tạo bài viết' }}</button>
</div>
