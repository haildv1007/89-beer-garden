@php
    $definition = $item['definition'];
    $input = $definition['input'] ?? null;
    $isImage = $input === 'image';
    $isMedia = in_array($input, ['image', 'media'], true);
    $fieldName = $isMedia ? "images[{$item['key']}]" : "values[{$item['key']}]";
    $errorKey = $isMedia ? "images.{$item['key']}" : "values.{$item['key']}";
    $current = $item['valid'] && !$definition['secret'] ? $item['setting']?->value : '';
@endphp
<div @class(['settings-field', 'settings-field--image' => $isMedia, 'settings-field--wide' => $input === 'textarea'])>
    <label for="setting-input-{{ $item['key'] }}">{{ __($definition['label']) }}</label>

    @if ($isMedia)
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="file"
            accept="{{ collect($definition['extensions'])->map(fn ($extension) => '.' . $extension)->join(',') }}">
        @if ($item['effective_image_url'])
            <div class="settings-image-preview">
                @if ($input === 'media')
                    <video src="{{ $item['effective_image_url'] }}" muted playsinline controls preload="metadata"></video>
                @else
                    <img src="{{ $item['effective_image_url'] }}" alt="{{ __($definition['label']) }}">
                @endif
            </div>
            <small class="settings-image-current">
                {{ $item['using_default_image'] ? 'Ảnh mặc định đang dùng:' : 'Ảnh đã cấu hình:' }}
                <a href="{{ $item['effective_image_url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['effective_image_url'] }}</a>
            </small>
        @endif
    @elseif ($input === 'toggle')
        <input type="hidden" name="{{ $fieldName }}" value="0">
        <label class="settings-toggle" for="setting-input-{{ $item['key'] }}">
            <input id="setting-input-{{ $item['key'] }}" name="{{ $fieldName }}" type="checkbox" value="1"
                @checked(old($errorKey, $current) === '1')>
            <span>Bật Gemini</span>
        </label>
    @elseif ($input === 'select')
        <select @class(['form-select', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}">
            @foreach ($definition['options'] as $value => $label)
                <option value="{{ $value }}" @selected(($current ?: ($definition['default'] ?? '')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    @elseif ($input === 'textarea')
        <textarea @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" rows="3" maxlength="{{ $definition['max'] }}">{{ old($errorKey, $current) }}</textarea>
    @elseif ($definition['type'] === 'integer')
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="number" min="{{ $definition['min'] ?? ($item['key'] === 'delivery_fee' ? 0 : 1) }}"
            max="{{ $definition['max'] ?? ($item['key'] === 'delivery_fee' ? 10000000 : 1440) }}" step="1"
            value="{{ old($errorKey, $current ?: ($definition['default'] ?? ($item['key'] === 'delivery_fee' ? 30000 : ''))) }}">
    @elseif ($item['key'] === 'google_translation_credentials_json')
        <textarea @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has($errorKey)])
            id="setting-input-{{ $item['key'] }}" name="{{ $fieldName }}" rows="5"
            placeholder="{{ $item['valid'] ? 'Đã thiết lập — dán JSON mới để thay thế' : 'Dán JSON service account để thiết lập' }}"></textarea>
    @else
        <input @class(['form-control', 'is-invalid' => $errors->has($errorKey)]) id="setting-input-{{ $item['key'] }}"
            name="{{ $fieldName }}" type="{{ $definition['secret'] ? 'password' : 'text' }}"
            maxlength="{{ $definition['max'] ?? 12000 }}" value="{{ old($errorKey, $current) }}"
            placeholder="{{ $definition['secret'] && $item['valid'] ? 'Đã thiết lập — nhập giá trị mới để thay thế' : '' }}"
            autocomplete="{{ $definition['secret'] ? 'new-password' : 'off' }}">
    @endif

    @if ($definition['description'] !== '')
        <small class="settings-field__help">{{ __($definition['description']) }}</small>
    @endif

    @if ($definition['secret'])
        <span @class([
            'settings-secret-status',
            'settings-secret-status--saved' => $item['valid'],
            'settings-secret-status--missing' => !$item['valid'],
        ])>
            {{ $item['valid'] ? 'Đã lưu an toàn' : 'Chưa thiết lập' }}
        </span>
    @endif

    @if ($item['key'] === 'google_oauth_client_id')
        <small>Redirect URI: <code>{{ route('auth.google.callback') }}</code></small>
    @elseif ($isMedia)
        @if ($input === 'media')
            <small>MP4 hoặc WEBM · tối đa 25MB. Nên dùng video ngắn 6–12 giây, không âm thanh và đã nén.</small>
        @else
            <small>PNG, JPG hoặc WEBP · tối đa 5MB. Chọn tệp mới rồi bấm “Lưu cài đặt” để thay ảnh.</small>
        @endif
    @endif
    @error($errorKey)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
