@extends('layouts.admin')
@section('title', __('employee.employees.create'))
@section('content')<h1>{{ __('employee.employees.create') }}</h1><form method="post" action="{{ route('admin.employees.store') }}">@include('admin.employees._form')</form>@endsection
