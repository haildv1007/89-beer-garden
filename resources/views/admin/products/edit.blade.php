@extends('layouts.admin')
@section('title', __('app.products.edit'))
@section('content')<h1>{{ __('app.products.edit') }}</h1><form method="post" action="{{ route('admin.products.update', $product) }}">@include('admin.products._form')</form>@endsection
