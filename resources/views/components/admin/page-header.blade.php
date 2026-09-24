@props(['title', 'eyebrow' => null, 'description' => null])
<header class="admin-page-header">
    <div>
        @if ($eyebrow)
            <span class="admin-page-eyebrow">{{ $eyebrow }}</span>
        @endif
        <h1>
            {{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="admin-page-actions">{{ $slot }}</div>
    @endif
</header>
