@extends('layouts.customer')
@section('title', __('app.menu.title') . ' — ' . __('app.name'))
@section('content')
    <div class="menu-page">
        <header class="menu-intro">
            <div class="menu-intro-copy">
                <h1>{{ __('app.menu.title') }}</h1>
                <p>{{ __('customer_ui.menu_copy') }}</p>
                <form class="menu-search" method="get" action="{{ route('customer.menu.index') }}">
                    <div class="menu-search-query"><label class="form-label"
                            for="q">{{ __('customer_ui.search_label') }}</label><input id="q"
                            class="form-control" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="{{ __('app.menu.search_placeholder') }}"></div>
                    <div class="menu-search-category"><label class="form-label"
                            for="category">{{ __('customer_ui.filter_label') }}</label><select id="category"
                            class="form-select" name="category">
                            <option value="">{{ __('app.menu.all_categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>
                                    {{ ($dynamicTranslations ?? collect())->get($category::class . ':' . $category->id . ':name', $category->name) }}
                                </option>
                            @endforeach
                        </select></div>
                    <button class="btn btn-primary menu-search-submit" type="submit" aria-label="{{ __('app.search') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                        <span>{{ __('app.search') }}</span>
                    </button>
                </form>
            </div>
            <figure class="menu-intro-visual"><img src="{{ asset('images/brand/grilled-feast.jpg') }}" alt="">
                <figcaption><span>89</span>{{ __('customer_ui.menu_heading') }}</figcaption>
            </figure>
        </header>

        <section class="menu-discovery" aria-label="{{ __('app.search') }}">
            <div class="sticky-categories">@include('customer.menu._categories', ['categories' => $categories])</div>
        </section>

        <div @class([
            'menu-results-heading',
            'menu-results-heading--quiet' => !(
                ($filters['q'] ?? null) || ($filters['category'] ?? null)
            ),
        ]) id="menu-catalogue">
            <div><span class="menu-results-kicker">89 BEER GARDEN</span>
                <p>{{ __('customer_ui.results', ['count' => $products->count()]) }}</p>
            </div>
            @if (($filters['q'] ?? null) || ($filters['category'] ?? null))
                <a class="text-link" href="{{ route('customer.menu.index') }}">{{ __('app.clear_filters') }}</a>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="empty-state"><span class="empty-state-mark" aria-hidden="true">◇</span>
                <h2 class="h4">{{ __('app.menu.no_results') }}</h2><a class="btn btn-outline-primary mt-2"
                    href="{{ route('customer.menu.index') }}">{{ __('app.clear_filters') }}</a>
            </div>
        @else
            <div class="menu-sections">
                @foreach ($menuSections as $section)
                    @php
                        $sectionCategory = $section['category'];
                    @endphp
                    <section @class(['menu-section', 'menu-section--featured' => $loop->first]) id="menu-category-{{ $sectionCategory->slug }}">
                        <header class="menu-section-heading">
                            <div><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h2>{{ ($dynamicTranslations ?? collect())->get($sectionCategory::class . ':' . $sectionCategory->id . ':name', $sectionCategory->name) }}
                                </h2>
                            </div>
                            <p>{{ __('customer_ui.results', ['count' => $section['products']->count()]) }}</p>
                        </header>
                        <div class="menu-product-grid">
                            @foreach ($section['products'] as $product)
                                @include('customer.menu._product-card')
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>

    <div class="modal fade product-quick-view" id="productQuickView" tabindex="-1" aria-labelledby="productQuickViewTitle"
        aria-hidden="true" data-more-label="{{ __('customer_ui.read_more') }}"
        data-less-label="{{ __('customer_ui.collapse') }}">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content"><button class="product-modal-close" type="button" data-bs-dismiss="modal"
                    aria-label="{{ __('customer_ui.close') }}">×</button>
                <div class="product-modal-layout">
                    <div class="product-modal-gallery" data-product-modal-gallery>
                        <figure class="product-modal-media"><img data-product-modal-image src="" alt=""
                                draggable="false"><video data-product-modal-video controls preload="metadata"
                                hidden></video><span class="product-modal-status" data-product-modal-status></span><span
                                class="product-modal-media-count" data-product-modal-media-count hidden><svg
                                    aria-hidden="true" viewBox="0 0 24 24">
                                    <path
                                        d="M5 7h2l1.2-2h7.6L17 7h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z">
                                    </path>
                                    <circle cx="12" cy="13" r="3"></circle>
                                </svg><span data-product-modal-media-count-text></span></span>
                            <div class="product-modal-thumbnails" data-product-modal-thumbnails
                                aria-label="{{ __('customer_ui.product_media') }}"></div>
                        </figure>
                    </div>
                    <div class="product-modal-content"><span class="product-category" data-product-modal-category></span>
                        <h2 id="productQuickViewTitle" data-product-modal-name></h2>
                        <div class="product-modal-description-wrap">
                            <p class="product-modal-description" data-product-modal-description></p><button
                                class="product-modal-description-toggle" type="button"
                                data-product-modal-description-toggle hidden>{{ __('customer_ui.read_more') }}</button>
                        </div>
                        <div class="product-modal-price" data-product-modal-price></div>
                        <form class="product-modal-form js-submit-once" data-add-to-cart method="post"
                            action="{{ route('customer.cart.items.store') }}">@csrf<input data-product-modal-id
                                type="hidden" name="product_id" value="">
                            <div class="product-quantity"><span>{{ __('customer_order.quantity') }}</span>
                                <div class="quantity-stepper"><button type="button" data-quantity-minus
                                        aria-label="−">−</button><input data-product-modal-quantity name="quantity"
                                        type="number" min="1" max="1000" value="1" required><button
                                        type="button" data-quantity-plus aria-label="+">+</button></div>
                            </div><button class="btn btn-reservation product-modal-submit"
                                data-product-modal-submit>{{ __('customer_order.add_to_cart') }}</button>
                        </form>
                        <a class="product-modal-detail" data-product-modal-detail
                            href="">{{ __('app.view_details') }} <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
