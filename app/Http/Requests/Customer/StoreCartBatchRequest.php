<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCartBatchRequest extends FormRequest
{
    private const ITEM_FIELDS = ['product_id', 'variant_id', 'quantity'];

    private const SERVER_CONTROLLED_FIELDS = [
        'price',
        'line_total',
        'estimated_total',
        'product_name',
        'category',
        'availability',
        'source',
        'customer_id',
        'dining_session_id',
        'order_id',
        'voucher',
        'discount',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1', 'max:6'],
            'items.*' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];

        foreach (self::SERVER_CONTROLLED_FIELDS as $field) {
            $rules[$field] = ['prohibited'];
            $rules["items.*.{$field}"] = ['prohibited'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['items'], self::SERVER_CONTROLLED_FIELDS) as $field) {
                    $validator->errors()->add($field, __('recommendation.validation.unknown_field'));
                }
                foreach ((array) $this->input('items', []) as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    foreach (array_diff(array_keys($item), self::ITEM_FIELDS, self::SERVER_CONTROLLED_FIELDS) as $field) {
                        $validator->errors()->add(
                            "items.{$index}.{$field}",
                            __('recommendation.validation.unknown_field'),
                        );
                    }
                }
            },
        ];
    }
}
