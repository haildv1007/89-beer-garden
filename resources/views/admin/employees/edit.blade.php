@extends('layouts.admin')
@section('title', __('employee.employees.edit'))
@section('content')<div class="employee-page employee-form-page"><a class="admin-back-link"
            href="{{ route('admin.employees.show', $employee) }}">← Hồ sơ nhân viên</a>
        <header>
            <h1>{{ __('employee.employees.edit') }}</h1>
            <p>{{ $employee->name }} · {{ $employee->employee_code }}</p>
        </header>
        <form method="post" action="{{ route('admin.employees.update', $employee) }}">@include('admin.employees._form')</form>
</div>@endsection
