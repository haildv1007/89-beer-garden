@extends('layouts.admin')
@section('title', $employee->name)
@section('content')
    <div class="employee-page employee-profile-page">
        <header class="employee-profile-header">
            <div>
                <h1>{{ $employee->name }}</h1>
                <p>{{ $employee->employee_code }} · {{ $employee->position ?: 'Chưa có chức danh' }}</p>
            </div>
            <div class="employee-profile-actions">
                <span class="badge text-bg-{{ $employee->status->value === 'active' ? 'success' : 'secondary' }}">{{ __('employee.statuses.' . $employee->status->value) }}</span>
                <a class="btn btn-primary" href="{{ route('admin.employees.edit', $employee) }}">{{ __('app.edit') }}</a>
                @can('employee.disable')
                    @if ($employee->status->value === 'active')
                        <form method="post" action="{{ route('admin.employees.disable', $employee) }}"
                            onsubmit="return confirm('{{ __('employee.employees.disable_confirm') }}')">
                            @csrf @method('patch')
                            <button class="btn btn-outline-danger">{{ __('employee.employees.disable') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </header>
        <section class="employee-profile-card">
            <h2>Thông tin nhân viên</h2>
            <dl>
                <div>
                    <dt>{{ __('employee.fields.employee_code') }}</dt>
                    <dd>{{ $employee->employee_code }}</dd>
                </div>
                <div>
                    <dt>{{ __('employee.fields.phone') }}</dt>
                    <dd>{{ $employee->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('employee.fields.position') }}</dt>
                    <dd>{{ $employee->position ?: '—' }}</dd>
                </div>
            </dl>
        </section>
        <section class="employee-profile-card employee-account-card">
            <h2>{{ __('employee.accounts.title') }}</h2>
            @if ($employee->user)
                <dl>
                    <div><dt>{{ __('employee.fields.email') }}</dt><dd>{{ $employee->user->email }}</dd></div>
                    <div><dt>{{ __('employee.fields.status') }}</dt><dd>{{ __('employee.statuses.' . $employee->user->status) }}</dd></div>
                    <div><dt>{{ __('employee.roles.current') }}</dt><dd>{{ $employee->user->role->name }}</dd></div>
                </dl>
                @can('permission.assign')
                    <form class="employee-role-form" method="post" action="{{ route('admin.employees.roles.update', $employee) }}">
                        @csrf @method('patch')
                        <label for="employee-role">Cập nhật vai trò</label>
                        <select class="form-select" id="employee-role" name="role_id">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected($employee->user->role_id === $role->id)>{{ $role->name }}
                                    </option>
                                @endforeach
                        </select>
                        <button class="btn btn-warning">{{ __('employee.roles.update') }}</button>
                    </form>
                @endcan
            @else
                <p class="text-secondary">{{ __('employee.accounts.none') }}</p>
                @can('permission.assign')
                    <form method="post" action="{{ route('admin.employees.accounts.store', $employee) }}"
                        class="border rounded p-3">@csrf
                        <div class="mb-3"><label class="form-label"
                                for="email">{{ __('employee.fields.email') }}</label><input type="email"
                                class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label"
                                    for="password">{{ __('employee.fields.password') }}</label><input type="password"
                                    class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                    required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label"
                                    for="password_confirmation">{{ __('employee.fields.password_confirmation') }}</label><input
                                    type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                                    required autocomplete="new-password"></div>
                        </div>
                        <div class="mb-3"><label class="form-label"
                                for="role_id">{{ __('employee.fields.role') }}</label><select
                                class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary">{{ __('employee.accounts.create') }}</button>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                data-bs-target="#link-existing-account-modal">{{ __('employee.accounts.link') }}</button>
                        </div>
                    </form>
                    <div class="modal fade" id="link-existing-account-modal" tabindex="-1"
                        aria-labelledby="link-existing-account-title" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="post" action="{{ route('admin.employees.accounts.link', $employee) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="link-existing-account-title">{{ __('employee.accounts.link') }}</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-secondary">{{ __('employee.accounts.link_help') }}</p>
                                        <div class="mb-3">
                                            <label class="form-label" for="existing_email">{{ __('employee.fields.email') }}</label>
                                            <input type="email" class="form-control @error('existing_email') is-invalid @enderror"
                                                id="existing_email" name="existing_email" value="{{ old('existing_email') }}" required>
                                            @error('existing_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div>
                                            <label class="form-label" for="existing_role_id">{{ __('employee.fields.role') }}</label>
                                            <select class="form-select" id="existing_role_id" name="role_id" required>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button class="btn btn-primary">{{ __('employee.accounts.link') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan
            @endif
        </section>
    </div>
@endsection
