@csrf
@if ($product->exists)
    @method('put')
@endif
<div class="product-editor-layout">
    <main>
        <section class="product-editor-card">
            <header>
                <h2>Thông tin cơ bản</h2>
                <p>Tên, nhóm món và nội dung khách hàng nhìn thấy trên thực đơn.</p>
            </header>
            <div class="product-form-grid">
                <div class="wide"><label class="form-label" for="name">{{ __('app.fields.name') }}</label><input
                        class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $product->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div><label class="form-label" for="category_id">{{ __('app.categories.title') }}</label><select
                        class="form-select @error('category_id') is-invalid @enderror" id="category_id"
                        name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div><label class="form-label" for="slug">{{ __('app.fields.slug') }}</label><input
                        class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                        value="{{ old('slug', $product->slug) }}" required>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="wide"><label class="form-label"
                        for="short_description">{{ __('app.fields.short_description') }}</label>
                    <textarea class="form-control @error('short_description') is-invalid @enderror" id="short_description"
                        name="short_description" rows="3" maxlength="500">{{ old('short_description', $product->short_description) }}</textarea>
                    <div class="form-text">Hiển thị ở trang chủ, menu và phần xem nhanh. Tối đa 500 ký tự.</div>
                    @error('short_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
        <section class="product-editor-card">
            <header>
                <h2>Mô tả sản phẩm</h2>
                <p>Trình bày thành phần, hương vị hoặc thông tin hữu ích cho khách.</p>
            </header>
            <label class="visually-hidden" for="description">{{ __('app.fields.product_description') }}</label>
            <textarea class="form-control product-description-input @error('description') is-invalid @enderror" id="description"
                name="description" rows="10" maxlength="20000">{{ old('description', $product->description) }}</textarea>
            <div class="form-text">Nội dung tối đa 20.000 ký tự; nên chia đoạn ngắn để dễ đọc.</div>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </section>
        <section class="admin-product-media product-editor-card">
            @php
                $mediaByPosition = $product->exists
                    ? $product->media->keyBy(fn($media) => $media->sort_order + 1)
                    : collect();
            @endphp
            <div class="d-flex justify-content-between gap-3 align-items-start">
                <div><span class="form-label d-block">Hình ảnh và video sản phẩm</span>
                    <p class="text-muted small mb-2">Tối đa 5 MB/tệp. Kéo các ô đã có media để đổi vị trí 1–5; vị trí 1
                        được ưu tiên làm ảnh đại diện.</p>
                </div><span class="admin-media-count" data-media-count>{{ $mediaByPosition->count() }}/5</span>
            </div>
            <div class="admin-media-grid" data-media-grid>
                @foreach (range(1, 5) as $position)
                    @php
                        $media = $mediaByPosition->get($position);
                    @endphp
                    <div class="admin-media-slot" data-media-slot
                        @if ($media) draggable="true" @endif>
                        <div class="admin-media-slot__heading">
                            <strong>Vị trí {{ $position }}</strong>
                            <span data-media-position-label>{{ $position === 1 ? 'Ảnh đại diện' : '' }}</span>
                        </div>
                        <div class="admin-media-preview {{ $media ? 'has-media' : '' }}" data-media-preview>
                            @if ($media)
                                @if ($media->media_type === 'video')
                                    <video src="{{ $media->url }}" controls preload="metadata"></video>
                                    <span class="admin-media-type">Video</span>
                                @else
                                    <img src="{{ $media->url }}" alt="Media vị trí {{ $position }}">
                                @endif
                                <button type="button" class="admin-media-remove" data-media-remove
                                    aria-label="Xóa media tại vị trí {{ $position }}">×</button>
                            @endif
                        </div>
                        @if ($media)
                            <input type="checkbox" class="visually-hidden" name="remove_media[]"
                                value="{{ $media->id }}" data-media-remove-input>
                        @endif
                        <input type="hidden" name="media_order[]" value="{{ $media ? $media->id : '' }}"
                            data-media-order>
                        @if ($media)
                            <span class="admin-media-drag-hint">⋮⋮ Kéo đổi vị trí</span>
                        @endif
                        <label class="admin-media-dropzone {{ $media ? 'd-none' : '' }}"
                            for="media_slot_{{ $position }}" data-media-dropzone>
                            <strong>Thêm tệp</strong>
                            <span>Kéo ảnh/video vào đây hoặc bấm để chọn</span>
                        </label>
                        <input type="file"
                            class="visually-hidden @error('media_slots.' . $position) is-invalid @enderror"
                            id="media_slot_{{ $position }}" name="media_slots[{{ $position }}]"
                            accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" data-media-input>
                        @error('media_slots.' . $position)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
            @error('media_slots')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </section>
    </main>
    <aside>
        <section class="product-editor-card product-selling-card">
            <header>
                <h2>Bán hàng</h2>
                <p>Thiết lập giá và khả năng xuất hiện trên menu.</p>
            </header>
            @unless ($product->exists)<div><label class="form-label"
                        for="price">{{ __('app.fields.price') }} (VNĐ)</label><input type="number" min="0"
                        class="form-control @error('price') is-invalid @enderror" id="price" name="price"
                        value="{{ old('price', 0) }}" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>@endunless
            <div><label class="form-label" for="status">{{ __('app.fields.status') }}</label><select
                    class="form-select" id="status" name="status">
                    <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>{{ __('app.statuses.active') }}</option>
                    <option value="inactive" @selected(old('status', $product->status) === 'inactive')>{{ __('app.statuses.inactive') }}</option>
                </select></div>
            <div><label class="form-label" for="is_available">{{ __('app.fields.availability') }}</label><select
                    class="form-select" id="is_available" name="is_available">
                    <option value="1" @selected((string) old('is_available', $product->is_available ?? true) === '1')>{{ __('app.products.available') }}</option>
                    <option value="0" @selected((string) old('is_available', $product->is_available ?? true) === '0')>{{ __('app.products.unavailable') }}</option>
                </select></div>
        </section>
    </aside>
</div>
<div class="product-editor-actions"><a class="btn btn-outline-secondary"
        href="{{ route('admin.products.index') }}">Hủy</a><button
        class="btn btn-primary">{{ __('app.save') }}</button></div>
