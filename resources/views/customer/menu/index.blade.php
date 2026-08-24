@extends('layouts.customer')

@section('title', __('app.menu.title').' — '.__('app.name'))

@section('content')
    <h1>{{ __('app.menu.title') }}</h1>
    <form class="row g-2 mb-4" method="get" action="{{ route('customer.menu.index') }}">
        <div class="col-12 col-md-6"><label class="visually-hidden" for="q">{{ __('app.search') }}</label><input id="q" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('app.menu.search_placeholder') }}"></div>
        <div class="col-8 col-md-4"><label class="visually-hidden" for="category">{{ __('app.categories.title') }}</label><select id="category" class="form-select" name="category"><option value="">{{ __('app.menu.all_categories') }}</option>@foreach ($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-4 col-md-2 d-grid"><button class="btn btn-primary">{{ __('app.search') }}</button></div>
    </form>
    @include('customer.menu._categories', ['categories' => $categories])
    @if ($products->isEmpty())
        <div class="alert alert-info mt-4">{{ __('app.menu.no_results') }} <a href="{{ route('customer.menu.index') }}">{{ __('app.clear_filters') }}</a></div>
    @else
        <div class="row g-4 mt-1">@foreach ($products as $product)<div class="col-12 col-sm-6 col-lg-4">@include('customer.menu._product-card')</div>@endforeach</div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
