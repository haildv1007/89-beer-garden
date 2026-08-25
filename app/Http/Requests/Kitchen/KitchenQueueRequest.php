<?php

namespace App\Http\Requests\Kitchen;

use Illuminate\Foundation\Http\FormRequest;

class KitchenQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kitchen.queue.view') === true;
    }

    public function rules(): array
    {
        return [];
    }
}
