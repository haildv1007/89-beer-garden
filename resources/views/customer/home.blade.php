@extends('layouts.customer')

@section('title', __('app.contexts.customer').' — '.__('app.name'))

@section('content')
    <section class="p-4 p-md-5 mb-5 bg-light rounded-3">
        <h1>{{ __('app.home.heading') }}</h1>
        <p class="lead">{{ __('app.home.lead') }}</p>
        <a class="btn btn-primary" href="{{ route('customer.menu.index') }}">{{ __('app.home.view_menu') }}</a>
    </section>
    @include('customer.menu._categories', ['categories' => $categories])
    <h2 class="mt-4">{{ __('app.home.latest_products') }}</h2>
    @if ($products->isEmpty())
        <div class="alert alert-info">{{ __('app.menu.empty') }}</div>
    @else
        <div class="row g-4">@foreach ($products as $product)<div class="col-12 col-sm-6 col-lg-4">@include('customer.menu._product-card')</div>@endforeach</div>
    @endif
@endsection
