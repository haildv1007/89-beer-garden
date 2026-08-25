@extends('layouts.admin')
@section('title', __('setting.title'))
@section('content')
<h1>{{ __('setting.title') }}</h1>
<p class="text-muted">{{ __('setting.intro') }}</p>
<div class="row g-4">
@foreach($settings as $item)
    <div class="col-lg-6"><section class="card h-100"><div class="card-body">
        <div class="d-flex justify-content-between gap-2"><h2 class="h4">{{ __($item['definition']['label']) }}</h2><span class="badge {{ $item['valid'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $item['valid'] ? __('setting.valid') : __('setting.invalid') }}</span></div>
        <p>{{ __($item['definition']['description']) }}</p>
        <dl class="row"><dt class="col-sm-4">{{ __('setting.type') }}</dt><dd class="col-sm-8">{{ __('setting.types.'.$item['definition']['type']) }}</dd><dt class="col-sm-4">{{ __('setting.current_value') }}</dt><dd class="col-sm-8"><code>{{ $item['setting']?->value ?? __('setting.missing') }}</code></dd><dt class="col-sm-4">{{ __('setting.updated_by') }}</dt><dd class="col-sm-8">{{ $item['setting']?->updatedBy?->name ?? '—' }}</dd><dt class="col-sm-4">{{ __('setting.updated_at') }}</dt><dd class="col-sm-8">{{ $item['setting']?->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd></dl>
        <form method="POST" action="{{ route('admin.settings.update', $item['key']) }}">@csrf @method('PUT')
            <label class="form-label" for="value-{{ $item['key'] }}">{{ __('setting.new_value') }}</label>
            @if($item['key'] === 'customer_ordering_enabled')
                <select class="form-select" id="value-{{ $item['key'] }}" name="value"><option value="true" @selected($item['setting']?->value === 'true')>{{ __('setting.enabled') }}</option><option value="false" @selected($item['setting']?->value === 'false')>{{ __('setting.disabled') }}</option></select>
            @else
                <input class="form-control" id="value-{{ $item['key'] }}" name="value" type="number" min="1" max="1440" step="1" value="{{ $item['valid'] ? $item['setting']?->value : '' }}" required>
            @endif
            <button class="btn btn-primary mt-3">{{ __('setting.save') }}</button>
        </form>
    </div></section></div>
@endforeach
</div>
@endsection
