<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class DiningSessionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100']];
    }
}
