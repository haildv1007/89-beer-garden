@props(['tone' => 'neutral'])
<span {{ $attributes->class(['admin-status-badge', 'is-' . $tone]) }}>{{ $slot }}</span>
