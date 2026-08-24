@extends('layouts.admin')
@section('title', __('table.admin.edit'))
@section('content')
    <h1>{{ __('table.admin.edit') }}</h1>
    <form method="post" action="{{ route('admin.restaurant-tables.update', $table) }}" @if($table->is_active) onsubmit="return document.getElementById('is_active').checked || confirm(@js(__('table.admin.confirm_deactivate')))" @endif>@csrf @method('put') @include('admin.restaurant-tables._form')</form>
@endsection
