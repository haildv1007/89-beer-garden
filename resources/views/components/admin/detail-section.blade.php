@props(['title' => null])
<section {{ $attributes->class(['admin-detail-section']) }}>
    @if ($title)
        <h2>{{ $title }}</h2>
    @endif{{ $slot }}
</section>
