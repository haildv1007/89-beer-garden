<nav class="d-flex flex-wrap gap-2" aria-label="{{ __('app.categories.title') }}">
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('customer.menu.index') }}">{{ __('app.menu.all_categories') }}</a>
    @foreach ($categories as $category)<a class="btn btn-sm btn-outline-secondary" href="{{ route('customer.menu.index', ['category' => $category->slug]) }}">{{ ($dynamicTranslations??collect())->get($category::class.':'.$category->id.':name',$category->name) }}</a>@endforeach
</nav>
