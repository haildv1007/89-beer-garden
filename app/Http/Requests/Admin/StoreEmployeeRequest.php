<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.manage') === true;
    }

    public function rules(): array
    {
        return [
            'employee_code' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('employees', 'employee_code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'user_id' => ['prohibited'],
            'role_id' => ['prohibited'],
            'status' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }
}
