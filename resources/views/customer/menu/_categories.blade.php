<nav class="category-pills" aria-label="{{ __('app.categories.title') }}">
    @php
        $selectedTag = collect($tags ?? [])->firstWhere('slug', request('tag'));
    @endphp
    @if ($showAll ?? true)
        <details class="tag-filter-dropdown">
            <summary class="category-pill active">
                <svg class="category-filter-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M14 4v6M4 17h2M10 17h10M6 14v6"/></svg>
                <span class="all-categories-label all-categories-label--desktop">{{ $selectedTag?->name ?? __('customer_ui.all') }}</span>
                <span class="all-categories-label all-categories-label--mobile">{{ $selectedTag?->name ?? __('customer_ui.all') }}</span>
                <svg class="category-dropdown-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
            </summary>
            <div class="tag-filter-menu">
                <div class="tag-filter-menu__heading"><span>{{ __('customer_ui.tag_filter_heading') }}</span><small>{{ __('customer_ui.tag_filter_help') }}</small></div>
                <a class="tag-filter-menu__all" href="{{ route('customer.menu.index', array_filter(['q' => request('q'), 'category' => request('category')])) }}#menu-catalogue"><span class="tag-filter-menu__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg></span><span>{{ __('customer_ui.all') }}</span>@if(!request('tag'))<svg class="tag-filter-check" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>@endif</a>
                @foreach ($tags ?? [] as $tag)
                    <a @class(['active' => request('tag') === $tag->slug]) href="{{ route('customer.menu.index', array_filter(['q' => request('q'), 'category' => request('category'), 'tag' => $tag->slug])) }}#menu-catalogue">
                        <span class="tag-filter-menu__icon" style="--filter-color:{{ $tag->icon_color ?: '#0b6b4f' }};--filter-bg:{{ $tag->background_color ?: '#e8f5ef' }}">
                            @if(str_contains($tag->icon_class ?? '', 'flame'))
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22c4 0 7-3 7-7 0-3-2-6-6-10 0 3-1 5-3 6 0-2-1-4-2-5-2 3-3 6-3 9 0 4 3 7 7 7Z"/><path d="M9 18c0 2 1 4 3 4s3-2 3-4c0-1-1-3-3-5 0 2-1 3-3 5Z"/></svg>
                            @elseif(str_contains($tag->icon_class ?? '', 'bolt'))
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>
                            @elseif(str_contains($tag->icon_class ?? '', 'clock'))
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h.01M3 11l8 8a2 2 0 0 0 3 0l5-5a2 2 0 0 0 0-3l-8-8H3z"/></svg>
                            @endif
                        </span><span>{{ $tag->name }}</span>@if(request('tag') === $tag->slug)<svg class="tag-filter-check" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>@endif
                    </a>
                @endforeach
            </div>
        </details>
    @endif
    <div class="category-pills-scroll">
        <a @class(['category-pill', 'active' => !request('category')]) href="#menu-catalogue"
            data-menu-category-link data-category-slug="">{{ __('customer_ui.all_categories') }}</a>
        @foreach ($categories as $category)
            @php
                $translationKey = $category::class . ':' . $category->id . ':name';
                $categoryName = ($dynamicTranslations ?? collect())->get($translationKey, $category->name);
            @endphp
            <a @class(['category-pill', 'active' => request('category') === $category->slug])
                href="#menu-category-{{ $category->slug }}" data-menu-category-link
                data-category-slug="{{ $category->slug }}">{{ $categoryName }}</a>
        @endforeach
    </div>
    <button class="category-scroll-button category-scroll-prev" type="button" aria-label="Xem các danh mục trước">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
    </button>
    <button class="category-scroll-button category-scroll-next" type="button" aria-label="Xem thêm danh mục">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
    </button>
</nav>
