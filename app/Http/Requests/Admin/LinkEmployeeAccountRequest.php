<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkEmployeeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee.manage') === true && $this->user()?->can('permission.assign') === true;
    }

    public function rules(): array
    {
        return [
            'existing_email' => ['required', 'email:rfc', 'max:255', Rule::exists('users', 'email')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->whereIn('code', Role::EMPLOYEE_CODES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->existing_email)) {
            $this->merge(['existing_email' => mb_strtolower(trim($this->existing_email))]);
        }
    }
}
