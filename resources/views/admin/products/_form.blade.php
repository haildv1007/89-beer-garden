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
                name="description" rows="10" data-post-editor data-upload-url="{{ route('admin.product-images.store') }}" data-editor-type="product">{{ old('description', $product->description) }}</textarea>
            <div class="form-text">Định dạng nội dung, chèn ảnh, liên kết và bảng. Tối đa 20.000 ký tự; ảnh tối đa 5 MB.</div>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </section>
        @can('product.update-price')
            @php
                $variantRows = old('variants', $product->exists ? $product->variants->map(fn($variant) => [
                    'id' => $variant->id, 'name' => $variant->name, 'price' => $variant->price,
                    'is_available' => $variant->is_available ? 1 : 0,
                ])->all() : []);
                $variantsEnabled = old('variants_present') !== null
                    ? (bool) old('variants_present')
                    : ($product->exists && $product->variants->isNotEmpty());
                $variantRows = array_pad($variantRows, max(3, count($variantRows) + 1), []);
            @endphp
            <section class="product-editor-card product-variants-card" data-product-variants>
                <header class="product-variants-heading">
                    <div><h2>Biến thể sản phẩm</h2><p>Bật khi món có nhiều set, kích cỡ hoặc mức giá.</p></div>
                    <label class="product-switch">
                        <input type="checkbox" name="variants_present" value="1" @checked($variantsEnabled) data-variants-toggle>
                        <span aria-hidden="true"></span><strong>Có biến thể</strong>
                    </label>
                </header>
                <div class="product-variant-editor" data-variants-fields @if(!$variantsEnabled) hidden @endif>
                    <div class="product-variant-columns" aria-hidden="true"><span>Tên biến thể</span><span>Giá bán (VNĐ)</span><span>Khả dụng</span></div>
                    @foreach ($variantRows as $index => $variant)
                        <div class="product-variant-row">
                            @if (!empty($variant['id']))<input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] }}">@endif
                            <input class="form-control" name="variants[{{ $index }}][name]" value="{{ $variant['name'] ?? '' }}" placeholder="Ví dụ: Set 400K">
                            <input class="form-control" type="number" min="0" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" placeholder="400000">
                            <label><input type="hidden" name="variants[{{ $index }}][is_available]" value="0"><input type="checkbox" name="variants[{{ $index }}][is_available]" value="1" @checked(($variant['is_available'] ?? 1) == 1)> Còn bán</label>
                        </div>
                    @endforeach
                    <p class="product-variant-help"><i class="ti ti-info-circle"></i> Chỉ điền những dòng cần dùng. Giá biến thể được áp dụng thống nhất trong menu, giỏ hàng và thanh toán.</p>
                </div>
                <div class="product-variant-disabled" data-variants-empty @if($variantsEnabled) hidden @endif>
                    <i class="ti ti-layers-off"></i><span>Sản phẩm đang dùng một mức giá bán.</span>
                </div>
            </section>
        @endcan
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
    <aside class="product-editor-sidebar">
        <section class="product-editor-card product-selling-card">
            <header>
                <h2>Bán hàng</h2>
                <p>Thiết lập giá và khả năng xuất hiện trên menu.</p>
            </header>
            @unless ($product->exists)<div><label class="form-label"
                        for="price">Giá bán (VNĐ)</label><input type="number" min="0"
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
        <section class="product-editor-card">
            <header><h2>Tag</h2><p>Một món có thể gắn nhiều tag.</p></header>
            <div class="product-tag-checklist">
                @foreach ($tags as $tag)
                    <label><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, array_map('intval', old('tags', $product->exists ? $product->tags->modelKeys() : []))))>
                        @if ($tag->icon_class)<i class="{{ $tag->icon_class }}" aria-hidden="true"></i>@endif {{ $tag->name }}</label>
                @endforeach
            </div>
            <a class="text-link" href="{{ route('admin.tags.index') }}">Quản lý tag</a>
        </section>
    </aside>
</div>
<div class="product-editor-actions"><div><a class="btn btn-outline-secondary"
        href="{{ route('admin.products.index') }}">Hủy</a><button
        class="btn btn-primary"><i class="ti ti-device-floppy"></i> {{ __('app.save') }}</button></div></div>
