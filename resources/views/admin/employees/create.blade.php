@extends('layouts.admin')
@section('title', __('employee.employees.create'))
@section('content')<div class="employee-page employee-form-page"><a class="admin-back-link"
            href="{{ route('admin.employees.index') }}">← Danh sách nhân viên</a>
        <header>
            <h1>{{ __('employee.employees.create') }}</h1>
            <p>Thêm thông tin cơ bản cho nhân sự mới.</p>
        </header>
        <form method="post" action="{{ route('admin.employees.store') }}">@include('admin.employees._form')</form>
</div>@endsection
