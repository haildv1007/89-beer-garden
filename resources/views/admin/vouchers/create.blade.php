@extends('layouts.admin')
@section('title', __('voucher.create'))
@section('content')<h1>{{ __('voucher.create') }}</h1><form method="post" action="{{ route('admin.vouchers.store') }}">@csrf @include('admin.vouchers._form')</form>@endsection
