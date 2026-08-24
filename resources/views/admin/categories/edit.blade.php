@extends('layouts.admin')
@section('title', __('app.categories.edit'))
@section('content')<h1>{{ __('app.categories.edit') }}</h1><form method="post" action="{{ route('admin.categories.update', $category) }}">@include('admin.categories._form')</form>@endsection
