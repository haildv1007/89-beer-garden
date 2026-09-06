@csrf
@if ($employee->exists)
    @method('put')
@endif
<section class="employee-form-card">
    <header>
        <h2>Thông tin nhân viên</h2>
        <p>Mã nhân viên dùng để nhận diện nội bộ và tra cứu nhanh.</p>
    </header>
    <div class="employee-form-grid">
        <div><label class="form-label" for="employee_code">{{ __('employee.fields.employee_code') }}</label><input
                class="form-control @error('employee_code') is-invalid @enderror" id="employee_code" name="employee_code"
                value="{{ old('employee_code', $employee->employee_code) }}" required>
            @error('employee_code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div><label class="form-label" for="name">{{ __('employee.fields.name') }}</label><input
                class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                value="{{ old('name', $employee->name) }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div><label class="form-label" for="phone">{{ __('employee.fields.phone') }}</label><input
                class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                value="{{ old('phone', $employee->phone) }}">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div><label class="form-label" for="position">{{ __('employee.fields.position') }}</label><input
                class="form-control @error('position') is-invalid @enderror" id="position" name="position"
                value="{{ old('position', $employee->position) }}">
            @error('position')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>
<div class="employee-form-actions"><a class="btn btn-outline-secondary"
        href="{{ route('admin.employees.index') }}">Hủy</a><button
        class="btn btn-primary">{{ __('app.save') }}</button></div>
