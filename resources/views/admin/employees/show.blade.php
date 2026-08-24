@extends('layouts.admin')
@section('title', $employee->name)
@section('content')
    <div class="d-flex justify-content-between align-items-start"><div><h1>{{ $employee->name }}</h1><span class="badge text-bg-{{ $employee->status->value === 'active' ? 'success' : 'secondary' }}">{{ __('employee.statuses.'.$employee->status->value) }}</span></div><a class="btn btn-primary" href="{{ route('admin.employees.edit', $employee) }}">{{ __('app.edit') }}</a></div>
    <dl class="row mt-4"><dt class="col-sm-3">{{ __('employee.fields.employee_code') }}</dt><dd class="col-sm-9">{{ $employee->employee_code }}</dd><dt class="col-sm-3">{{ __('employee.fields.phone') }}</dt><dd class="col-sm-9">{{ $employee->phone ?: '—' }}</dd><dt class="col-sm-3">{{ __('employee.fields.position') }}</dt><dd class="col-sm-9">{{ $employee->position ?: '—' }}</dd></dl>
    @can('employee.disable')
        @if($employee->status->value === 'active')
            <form method="post" action="{{ route('admin.employees.disable', $employee) }}" onsubmit="return confirm('{{ __('employee.employees.disable_confirm') }}')">@csrf @method('patch')<button class="btn btn-outline-danger">{{ __('employee.employees.disable') }}</button></form>
        @endif
    @endcan
    <hr class="my-4"><h2 class="h4">{{ __('employee.accounts.title') }}</h2>
    @if($employee->user)
        <dl class="row"><dt class="col-sm-3">{{ __('employee.fields.email') }}</dt><dd class="col-sm-9">{{ $employee->user->email }}</dd><dt class="col-sm-3">{{ __('employee.fields.status') }}</dt><dd class="col-sm-9">{{ __('employee.statuses.'.$employee->user->status) }}</dd><dt class="col-sm-3">{{ __('employee.roles.current') }}</dt><dd class="col-sm-9"><span class="badge text-bg-dark">{{ $employee->user->role->name }}</span></dd></dl>
        @can('permission.assign')<form class="row g-2" method="post" action="{{ route('admin.employees.roles.update', $employee) }}">@csrf @method('patch')<div class="col-sm-6"><select class="form-select" name="role_id">@foreach($roles as $role)<option value="{{ $role->id }}" @selected($employee->user->role_id === $role->id)>{{ $role->name }}</option>@endforeach</select></div><div class="col-sm-auto"><button class="btn btn-warning">{{ __('employee.roles.update') }}</button></div></form>@endcan
    @else
        <p class="text-secondary">{{ __('employee.accounts.none') }}</p>
        @can('permission.assign')
        <form method="post" action="{{ route('admin.employees.accounts.store', $employee) }}" class="border rounded p-3">@csrf
            <div class="mb-3"><label class="form-label" for="email">{{ __('employee.fields.email') }}</label><input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label" for="password">{{ __('employee.fields.password') }}</label><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6 mb-3"><label class="form-label" for="password_confirmation">{{ __('employee.fields.password_confirmation') }}</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div></div>
            <div class="mb-3"><label class="form-label" for="role_id">{{ __('employee.fields.role') }}</label><select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select>@error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><button class="btn btn-primary">{{ __('employee.accounts.create') }}</button>
        </form>
        @endcan
    @endif
@endsection
