@extends('layouts.admin')
@section('title', __('employee.employees.edit'))
@section('content')<h1>{{ __('employee.employees.edit') }}</h1><form method="post" action="{{ route('admin.employees.update', $employee) }}">@include('admin.employees._form')</form>@endsection
