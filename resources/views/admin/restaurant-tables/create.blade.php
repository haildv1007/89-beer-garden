@extends('layouts.admin')
@section('title', __('table.admin.create'))
@section('content')
    <h1>{{ __('table.admin.create') }}</h1>
    <form method="post" action="{{ route('admin.restaurant-tables.store') }}">@csrf @include('admin.restaurant-tables._form')</form>
@endsection
