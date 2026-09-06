<?php

namespace App\Http\Requests\POS;

use App\Http\Requests\Customer\StoreReservationRequest;

class StoreManualReservationRequest extends StoreReservationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservation.manage') === true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
