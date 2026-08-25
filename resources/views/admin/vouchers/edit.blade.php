@extends('layouts.admin')
@section('title', $voucher->code)
@section('content')
<h1>{{ $voucher->code }}</h1>
@if($voucher->bills_count)<div class="alert alert-warning">{{ __('voucher.historical_locked') }}</div>@endif
<form method="post" action="{{ route('admin.vouchers.update', $voucher) }}">@csrf @method('put') @include('admin.vouchers._form')</form>
<form class="mt-3" method="post" action="{{ route('admin.vouchers.destroy', $voucher) }}">@csrf @method('delete')<button class="btn btn-outline-danger">{{ __('app.delete') }}</button></form>
@endsection
