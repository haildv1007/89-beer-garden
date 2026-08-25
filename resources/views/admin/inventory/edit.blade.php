@extends('layouts.admin')
@section('title', __('inventory.edit'))
@section('content')<h1>{{ __('inventory.edit') }} — {{ $item->sku }}</h1><form method="post" action="{{ route('admin.inventory-items.update', $item) }}">@csrf @method('put') @include('admin.inventory._form')</form>@endsection
