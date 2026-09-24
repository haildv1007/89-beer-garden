<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransferDiningSessionTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true
            && $this->user()?->can('table.operate') === true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'integer', 'exists:restaurant_tables,id'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'Vui lòng chọn bàn mới.',
            'reason.required' => 'Vui lòng nhập lý do đổi bàn.',
        ];
    }
}
