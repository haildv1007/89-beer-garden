<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['employee_code'] = [
            'required',
            'string',
            'max:255',
            'alpha_dash:ascii',
            Rule::unique('employees', 'employee_code')->ignore($this->route('employee')),
        ];

        return $rules;
    }
}
