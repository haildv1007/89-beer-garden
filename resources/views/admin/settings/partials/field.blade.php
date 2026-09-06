@php
    $definition = $item['definition'];
    $input = $definition['input'] ?? null;
    $isImage = $input === 'image';
    $fieldName = $isImage ? "images[{$item['key']}]" : "values[{$item['key']}]";
    $errorKey = $isImage ? "images.{$item['key']}" : "values.{$item['key']}";
    $current = $item['valid'] && !$definition['secret'] ? $item['setting']?->value : '';
@endphp
<div @class(['settings-field', 'settings-field--image' => $isImage, 'settings-field--wide' => $input === 'textarea'])>
    <label for="setting-input-{{ $item['key'] }}">{{ __($definition['label']) }}</label>

    @if ($isImage)
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="file"
            accept="{{ collect($definition['extensions'])->map(fn ($extension) => '.' . $extension)->join(',') }}">
        @if ($item['valid'])
            <div class="settings-image-preview">
                <img src="{{ Storage::disk('public')->url($item['setting']->value) }}" alt="{{ __($definition['label']) }}">
            </div>
        @endif
    @elseif ($input === 'select')
        <select @class(['form-select', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}">
            @foreach ($definition['options'] as $value => $label)
                <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
            @endforeach
        </select>
    @elseif ($input === 'textarea')
        <textarea @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" rows="3" maxlength="{{ $definition['max'] }}">{{ old($errorKey, $current) }}</textarea>
    @elseif ($item['key'] === 'customer_ordering_enabled')
        <select @class(['form-select', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}">
            <option value="true" @selected($current === 'true')>Bật</option>
            <option value="false" @selected($current === 'false')>Tắt</option>
        </select>
    @elseif ($definition['type'] === 'integer')
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="number" min="{{ $item['key'] === 'delivery_fee' ? 0 : 1 }}"
            max="{{ $item['key'] === 'delivery_fee' ? 10000000 : 1440 }}" step="1"
            value="{{ old($errorKey, $current ?: ($item['key'] === 'delivery_fee' ? 30000 : '')) }}">
    @elseif ($item['key'] === 'google_translation_credentials_json')
        <textarea @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has($errorKey)])
            id="setting-input-{{ $item['key'] }}" name="{{ $fieldName }}" rows="5"
            placeholder="Dán JSON service account mới để thay thế"></textarea>
    @else
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="{{ $definition['secret'] ? 'password' : 'text' }}"
            maxlength="{{ $definition['max'] ?? 12000 }}" value="{{ old($errorKey, $current) }}"
            placeholder="{{ $definition['secret'] && $item['valid'] ? 'Đã thiết lập — nhập giá trị mới để thay thế' : '' }}"
            autocomplete="{{ $definition['secret'] ? 'new-password' : 'off' }}">
    @endif

    @if ($item['key'] === 'google_oauth_client_id')
        <small>Redirect URI: <code>{{ route('auth.google.callback') }}</code></small>
    @elseif ($isImage)
        <small>PNG, JPG hoặc WEBP · tối đa 5MB</small>
    @endif
    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
