@extends('layouts.admin')
@section('title', __('app.categories.create'))
@section('content')<h1>{{ __('app.categories.create') }}</h1><form method="post" action="{{ route('admin.categories.store') }}">@include('admin.categories._form')</form>@endsection
