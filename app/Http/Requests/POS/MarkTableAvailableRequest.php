<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class MarkTableAvailableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('table.operate') === true;
    }

    public function rules(): array
    {
        return [
            'runtime_status' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
