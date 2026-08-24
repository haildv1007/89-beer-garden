@extends('layouts.admin')
@section('title', __('app.products.create'))
@section('content')<h1>{{ __('app.products.create') }}</h1>@if($categories->isEmpty())<div class="alert alert-warning">{{ __('app.products.category_required') }}</div>@else<form method="post" action="{{ route('admin.products.store') }}">@include('admin.products._form')</form>@endif@endsection
