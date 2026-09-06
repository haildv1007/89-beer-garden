<section class="settings-panel" id="settings-{{ $groupId }}">
    <header class="settings-panel__header">
        <h2>{{ $group['label'] }}</h2>
        <p>{{ $group['description'] }}</p>
    </header>
    @if ($groupId === 'scripts')
        <div class="settings-script-notice">Chỉ sử dụng mã từ nguồn tin cậy. Nội dung sẽ được chèn trực tiếp vào website public.</div>
    @endif
    <div class="settings-panel__body">
        @foreach ($group['keys'] as $key)
            @php($item = $settings->get($key))
            @if ($item)
                @include('admin.settings.partials.field', ['item' => $item])
            @endif
        @endforeach
    </div>
</section>
