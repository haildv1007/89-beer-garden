<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelDiningSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true
            && $this->user()?->can('table.operate') === true;
    }

    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return ['cancellation_reason.required' => 'Vui lòng nhập lý do hủy phiên.'];
    }
}
