<nav class="category-pills" aria-label="{{ __('app.categories.title') }}">
    @if ($showAll ?? true)
        <a class="category-pill active"
            href="{{ route('customer.menu.index') }}#menu-catalogue">{{ __('app.menu.all_categories') }}</a>
    @endif
    @foreach ($categories as $category)
        @php
            $translationKey = $category::class . ':' . $category->id . ':name';
            $categoryName = ($dynamicTranslations ?? collect())->get($translationKey, $category->name);
        @endphp
        <a class="category-pill"
            href="{{ route('customer.menu.index') }}#menu-category-{{ $category->slug }}">{{ $categoryName }}</a>
    @endforeach
</nav>
