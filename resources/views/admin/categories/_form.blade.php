@csrf
@if ($category->exists)
    @method('put')
@endif
<div class="mb-3">
    <label class="form-label" for="name">{{ __('app.fields.name') }}</label><input
        class="form-control @error('name') is-invalid @enderror" id="name" name="name"
        value="{{ old('name', $category->name) }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3"><label class="form-label" for="slug">{{ __('app.fields.slug') }}</label><input
        class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
        value="{{ old('slug', $category->slug) }}" required>
    @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3"><label class="form-label" for="description">{{ __('app.fields.description') }}</label>
    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label" for="status">{{ __('app.fields.status') }}</label><select
            class="form-select" id="status" name="status">
            <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>{{ __('app.statuses.active') }}</option>
            <option value="inactive" @selected(old('status', $category->status) === 'inactive')>{{ __('app.statuses.inactive') }}</option>
        </select></div>
    <div class="col-md-6 mb-3"><label class="form-label"
            for="sort_order">{{ __('app.fields.sort_order') }}</label><input type="number" min="0"
            class="form-control" id="sort_order" name="sort_order"
            value="{{ old('sort_order', $category->sort_order ?? 0) }}" required></div>
</div>
<button class="btn btn-primary">{{ __('app.save') }}</button>
