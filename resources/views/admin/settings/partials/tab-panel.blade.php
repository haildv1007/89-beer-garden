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
            @if ($groupId === 'connections')
                @if ($key === \App\Services\SystemSetting\SystemSettingCatalog::GOOGLE_OAUTH_CLIENT_ID)
                    <div class="settings-connection-heading">
                        <h3>Đăng nhập Google</h3>
                        <p>Cho phép khách hàng đăng nhập bằng tài khoản Google.</p>
                    </div>
                @elseif ($key === \App\Services\SystemSetting\SystemSettingCatalog::GOOGLE_TRANSLATION_PROJECT_ID)
                    <div class="settings-connection-heading">
                        <h3>Dịch tự động</h3>
                        <p>Kết nối Google Cloud Translation cho nội dung đa ngôn ngữ.</p>
                    </div>
                @elseif ($key === \App\Services\SystemSetting\SystemSettingCatalog::GEMINI_ENABLED)
                    <div class="settings-connection-heading">
                        <h3>Gemini AI</h3>
                        <p>Bật trợ lý, nhập API key và chọn model phù hợp với chi phí vận hành.</p>
                    </div>
                @endif
            @endif
            @php($item = $settings->get($key))
            @if ($item)
                @include('admin.settings.partials.field', ['item' => $item])
            @endif
        @endforeach
    </div>
</section>
