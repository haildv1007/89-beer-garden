<div class="news-meta">
    <a
        href="{{ route('customer.posts.index', ['category' => $entry->category]) }}">{{ $categoryLabels[$entry->category] }}</a>
    <time datetime="{{ $entry->published_at->toAtomString() }}" class="news-date">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7"
            aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 11h18" />
        </svg>
        <span>{{ __('customer_ui.news_published_on', ['date' => $entry->published_at->format('d/m/Y')]) }}</span>
    </time>
</div>
