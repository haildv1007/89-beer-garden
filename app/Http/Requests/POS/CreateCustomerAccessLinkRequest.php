<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerAccessLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dining-session.view') === true
            && $this->user()?->can('order.create') === true;
    }

    public function rules(): array
    {
        return ['dining_session_id' => ['prohibited'], 'expires_at' => ['prohibited'], 'signature' => ['prohibited']];
    }
}
