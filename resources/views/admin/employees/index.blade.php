@extends('layouts.admin')
@section('title', __('employee.employees.title'))
@section('content')
    <div class="employee-page employee-list-page">
        <div class="admin-section-heading employee-page-heading">
            <div>
                <h1>{{ __('employee.employees.title') }}</h1>
                <p>Quản lý hồ sơ, trạng thái làm việc và tài khoản truy cập của nhân sự.</p>
            </div>
            @can('employee.manage')
                <a class="btn btn-primary" href="{{ route('admin.employees.create') }}">+
                    {{ __('employee.employees.create') }}</a>
            @endcan
        </div>
        <form class="row g-2 employee-list-filter" method="get">
            <div class="col-lg-5"><input class="form-control" name="q" value="{{ $filters['search'] }}"
                    placeholder="{{ __('app.search') }}"></div>
            <div class="col-sm-5 col-lg-3"><select class="form-select" name="status">
                    <option value="">{{ __('employee.filters.all_statuses') }}</option>
                    <option value="active" @selected($filters['status'] === 'active')>{{ __('employee.statuses.active') }}</option>
                    <option value="disabled" @selected($filters['status'] === 'disabled')>{{ __('employee.statuses.disabled') }}</option>
                </select></div>
            <div class="col-sm-5 col-lg-2"><select class="form-select" name="role">
                    <option value="">{{ __('employee.filters.all_roles') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->code }}" @selected($filters['role'] === $role->code)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 d-grid"><button class="btn btn-outline-secondary">{{ __('app.search') }}</button></div>
        </form>
        @if ($employees->isEmpty())
            <div class="alert alert-info">{{ __('employee.employees.empty') }}</div>
        @else
            <div class="admin-data-shell employee-list-shell">
                <table class="table table-hover align-middle admin-data-table employee-list-table">
                    <thead>
                        <tr>
                            <th>{{ __('employee.fields.employee_code') }}</th>
                            <th>{{ __('employee.fields.name') }}</th>
                            <th>{{ __('employee.fields.position') }}</th>
                            <th>{{ __('employee.fields.status') }}</th>
                            <th>{{ __('employee.fields.email') }}</th>
                            <th>{{ __('employee.fields.role') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>{{ $employee->employee_code }}</td>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->position ?: '—' }}</td>
                                <td>@include('admin.partials.status-badge', [
                                    'active' => $employee->status->value === 'active',
                                    'label' => __('employee.statuses.' . $employee->status->value),
                                ])</td>
                                <td>{{ $employee->user?->email ?: '—' }}</td>
                                <td>
                                    @if ($employee->user)
                                    <span class="badge text-bg-dark">{{ $employee->user->role->name }}</span>@else—
                                    @endif
                                </td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('admin.employees.show', $employee) }}">{{ __('app.view_details') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>{{ $employees->links() }}
        @endif
    </div>
@endsection
