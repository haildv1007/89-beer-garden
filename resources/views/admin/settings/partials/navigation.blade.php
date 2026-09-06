<nav class="settings-nav" aria-label="Nhóm cấu hình">
    @foreach ($groups as $groupId => $group)
        <a @class(['active' => $selectedGroup === $groupId])
            href="{{ route('admin.settings.index', ['tab' => $groupId]) }}"
            @if ($selectedGroup === $groupId) aria-current="page" @endif>
            {{ $group['label'] }}
        </a>
    @endforeach
</nav>
