@extends('layouts.admin')
@section('title', 'Quản lý tag')
@section('content')
@php($icons = ['flame','clock-10','clock','clock-hour-4','tools-kitchen-2','bolt','star','heart','chef-hat','beer','salad','soup','pepper','meat'])
<div class="tag-admin-page">
    <header class="admin-section-heading tag-page-heading">
        <div><span class="admin-page-eyebrow">THỰC ĐƠN</span><h1>Quản lý tag món ăn</h1><p>Tạo nhãn trực quan cho món nổi bật, thời gian chế biến và trạng thái phục vụ.</p></div>
        <a class="btn btn-outline-primary" href="{{ route('admin.categories.index') }}"><i class="ti ti-arrow-left"></i> Danh mục</a>
    </header>

    <section class="tag-create-panel">
        <div class="tag-panel-heading">
            <div><span class="tag-panel-icon"><i class="ti ti-tag-plus"></i></span><div><h2>Tạo tag mới</h2><p>Chọn biểu tượng và màu sắc để nhận biết nhanh trên thực đơn.</p></div></div>
            <div class="tag-live-preview" data-tag-preview style="--tag-color:#0b6b4f;--tag-bg:#e8f5ef"><i hidden></i><span>Tag mẫu</span></div>
        </div>
        <form method="post" action="{{ route('admin.tags.store') }}" class="tag-create-form" data-tag-form>@csrf
            <label><span>Tên tag *</span><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Ví dụ: Món mới" required data-tag-name></label>
            <label><span>Slug *</span><input class="form-control" name="slug" value="{{ old('slug') }}" placeholder="mon-moi" required></label>
            <label><span>Icon class <small>(không bắt buộc)</small></span><input class="form-control" name="icon_class" value="{{ old('icon_class') }}" placeholder="Ví dụ: ti ti-flame" data-icon-class></label>
            <label><span>Thứ tự</span><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}" required></label>
            <div class="tag-color-fields">
                <label><span>Màu icon</span><span class="color-input"><input type="color" name="icon_color" value="{{ old('icon_color', '#0b6b4f') }}" data-tag-color><input type="text" value="{{ old('icon_color', '#0b6b4f') }}" maxlength="7" aria-label="Mã màu icon" data-color-hex></span></label>
                <label><span>Màu nền</span><span class="color-input"><input type="color" name="background_color" value="{{ old('background_color', '#e8f5ef') }}" data-tag-background><input type="text" value="{{ old('background_color', '#e8f5ef') }}" maxlength="7" aria-label="Mã màu nền" data-color-hex></span></label>
            </div>
            <div class="tag-icon-picker" data-icon-picker>@foreach ($icons as $icon)<button type="button" data-icon-value="ti ti-{{ $icon }}" title="ti ti-{{ $icon }}"><i class="ti ti-{{ $icon }}"></i></button>@endforeach</div>
            <button class="btn btn-primary tag-create-submit"><i class="ti ti-plus"></i> Tạo tag</button>
        </form>
    </section>

    <section class="tag-list-panel">
        <div class="tag-panel-heading"><div><span class="tag-panel-icon"><i class="ti ti-tags"></i></span><div><h2>Danh sách tag</h2><p>{{ $tags->count() }} tag đang được sử dụng trong hệ thống.</p></div></div></div>
        <div class="tag-management-list">
            @forelse ($tags as $tag)
                <article class="tag-management-row">
                    <div class="tag-row-summary">
                        <span class="tag-live-preview" style="--tag-color:{{ $tag->icon_color }};--tag-bg:{{ $tag->background_color }}">@if($tag->icon_class)<i class="{{ $tag->icon_class }}"></i>@endif<span>{{ $tag->name }}</span></span>
                        <span class="tag-usage"><strong>{{ $tag->products_count }}</strong> món</span>
                    </div>
                    <form method="post" action="{{ route('admin.tags.update', $tag) }}" class="tag-update-form" data-tag-form>@csrf @method('put')
                        <label><span>Tên</span><input class="form-control" name="name" value="{{ $tag->name }}" required data-tag-name></label>
                        <label><span>Slug</span><input class="form-control" name="slug" value="{{ $tag->slug }}" required></label>
                        <label><span>Icon class</span><input class="form-control" name="icon_class" value="{{ $tag->icon_class }}" data-icon-class></label>
                        <label><span>Màu icon</span><span class="color-input"><input type="color" name="icon_color" value="{{ $tag->icon_color }}" data-tag-color><input type="text" value="{{ $tag->icon_color }}" maxlength="7" aria-label="Mã màu icon" data-color-hex></span></label>
                        <label><span>Màu nền</span><span class="color-input"><input type="color" name="background_color" value="{{ $tag->background_color }}" data-tag-background><input type="text" value="{{ $tag->background_color }}" maxlength="7" aria-label="Mã màu nền" data-color-hex></span></label>
                        <label class="tag-order-field"><span>Thứ tự</span><input class="form-control" type="number" min="0" name="sort_order" value="{{ $tag->sort_order }}" required></label>
                        <button class="btn btn-outline-primary"><i class="ti ti-device-floppy"></i> Lưu</button>
                    </form>
                    <form method="post" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('Xóa tag {{ addslashes($tag->name) }}? Tag sẽ được gỡ khỏi các sản phẩm đang sử dụng.')">@csrf @method('delete')<button class="tag-delete-button" aria-label="Xóa tag {{ $tag->name }}"><i class="ti ti-trash"></i></button></form>
                </article>
            @empty <div class="admin-empty-state"><i class="ti ti-tags-off"></i><strong>Chưa có tag</strong><span>Tạo tag đầu tiên ở biểu mẫu phía trên.</span></div> @endforelse
        </div>
    </section>
</div>
<script>
document.querySelectorAll('[data-tag-form]').forEach((form) => {
    const preview = form.closest('.tag-create-panel')?.querySelector('[data-tag-preview]');
    const sync = () => {
        if (preview) { const iconClass = form.querySelector('[data-icon-class]').value.trim(); const icon = preview.querySelector('i'); preview.style.setProperty('--tag-color', form.querySelector('[data-tag-color]').value); preview.style.setProperty('--tag-bg', form.querySelector('[data-tag-background]').value); preview.querySelector('span').textContent = form.querySelector('[data-tag-name]').value || 'Tag mẫu'; icon.className = iconClass; icon.hidden = !iconClass; }
    };
    form.querySelectorAll('.color-input').forEach((group) => {
        const picker = group.querySelector('input[type="color"]'); const hex = group.querySelector('[data-color-hex]');
        picker.addEventListener('input', () => { hex.value = picker.value.toUpperCase(); sync(); });
        hex.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(hex.value)) { picker.value = hex.value; sync(); } });
        hex.addEventListener('blur', () => { if (!/^#[0-9a-f]{6}$/i.test(hex.value)) hex.value = picker.value.toUpperCase(); });
    });
    form.querySelectorAll('input:not([type="color"]):not([data-color-hex])').forEach((input) => input.addEventListener('input', sync));
    form.querySelectorAll('[data-icon-picker] button').forEach((button) => button.addEventListener('click', () => { form.querySelector('[data-icon-class]').value = button.dataset.iconValue; sync(); }));
    sync();
});
</script>
@endsection
