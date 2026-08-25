@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">{{ __('inventory.fields.sku') }}</label><input class="form-control" required maxlength="255" name="sku" value="{{ old('sku', $item->sku) }}"></div>
    <div class="col-md-8"><label class="form-label">{{ __('inventory.fields.name') }}</label><input class="form-control" required maxlength="255" name="name" value="{{ old('name', $item->name) }}"></div>
    <div class="col-md-4"><label class="form-label">{{ __('inventory.fields.unit') }}</label><input class="form-control" required maxlength="100" name="unit" value="{{ old('unit', $item->unit) }}"></div>
    <div class="col-md-4"><label class="form-label">{{ __('inventory.fields.minimum_stock') }}</label><input class="form-control" required type="number" min="0" name="minimum_stock" value="{{ old('minimum_stock', $item->minimum_stock) }}"></div>
    <div class="col-md-4"><label class="form-label">{{ __('inventory.fields.status') }}</label><select class="form-select" name="status"><option value="active" @selected(old('status', $item->status) === 'active')>{{ __('inventory.statuses.active') }}</option><option value="inactive" @selected(old('status', $item->status) === 'inactive')>{{ __('inventory.statuses.inactive') }}</option></select></div>
    <div class="col-12"><label class="form-label">{{ __('inventory.fields.product') }}</label><select class="form-select" name="product_id"><option value="">{{ __('inventory.no_product') }}</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) old('product_id', $item->product_id) === (string) $product->id)>{{ $product->name }}</option>@endforeach</select></div>
</div>
<p class="form-text mt-3">{{ __('inventory.stock_via_movement') }}</p><button class="btn btn-primary">{{ __('app.save') }}</button>
