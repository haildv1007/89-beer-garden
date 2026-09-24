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
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,printing,printed,failed'],
            'type' => ['nullable', 'in:order,adjustment,cancellation'],
        ];
    }
}
