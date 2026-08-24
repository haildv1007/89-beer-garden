<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission.assign') === true;
    }

    public function rules(): array
    {
        return ['role_id' => ['required', 'integer', Rule::exists('roles', 'id')->whereIn('code', Role::EMPLOYEE_CODES)]];
    }
}
