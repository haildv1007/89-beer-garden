@extends('layouts.admin')
@section('title', __('inventory.create'))
@section('content')<h1>{{ __('inventory.create') }}</h1>
    <form method="post" action="{{ route('admin.inventory-items.store') }}">@csrf @include('admin.inventory._form')</form>
@endsection
