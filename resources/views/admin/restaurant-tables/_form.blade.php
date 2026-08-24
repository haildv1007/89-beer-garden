@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="code">{{ __('table.fields.code') }}</label><input class="form-control" id="code" name="code" required maxlength="255" value="{{ old('code', $table->code) }}"></div>
    <div class="col-md-8"><label class="form-label" for="name">{{ __('table.fields.name') }}</label><input class="form-control" id="name" name="name" required maxlength="255" value="{{ old('name', $table->name) }}"></div>
    <div class="col-md-4"><label class="form-label" for="capacity">{{ __('table.fields.capacity') }}</label><input class="form-control" id="capacity" type="number" min="1" name="capacity" required value="{{ old('capacity', $table->capacity) }}"></div>
    <div class="col-md-8"><label class="form-label" for="location">{{ __('table.fields.location') }}</label><input class="form-control" id="location" name="location" maxlength="255" value="{{ old('location', $table->location) }}"></div>
    <div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check"><input class="form-check-input" id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $table->is_active))><label class="form-check-label" for="is_active">{{ __('table.active_for_service') }}</label></div></div>
    @if ($table->exists)<div class="col-12"><span class="text-muted">{{ __('table.runtime_server_owned') }}</span> @include('partials.table-status-badge', ['status' => $table->runtime_status])</div>@endif
</div>
<div class="mt-4"><button class="btn btn-primary">{{ __('app.save') }}</button> <a class="btn btn-outline-secondary" href="{{ $table->exists ? route('admin.restaurant-tables.show', $table) : route('admin.restaurant-tables.index') }}">{{ __('table.cancel') }}</a></div>
