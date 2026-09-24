@props(['title' => null, 'description' => null])
<section {{ $attributes->class(['admin-form-section']) }}>
    @if ($title)
        <h2>{{ $title }}</h2>
        @endif @if ($description)
            <p>{{ $description }}</p>
        @endif{{ $slot }}
</section>
