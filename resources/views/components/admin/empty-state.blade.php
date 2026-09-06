@props(['title', 'description' => null])
<div class="admin-empty-state" role="status"><span aria-hidden="true">◇</span><strong>{{ $title }}</strong>
    @if ($description)
        <p>{{ $description }}</p>
    @endif{{ $slot }}
</div>
