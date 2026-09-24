<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAIChatMessageRequest extends FormRequest
{
    private const SERVER_FIELDS = [
        'user_id', 'customer_id', 'role', 'intent', 'context', 'products', 'recommendation',
        'actions', 'order_id', 'reservation_id', 'price', 'source', 'system_prompt', 'tool', 'tool_result',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            $this->merge(['message' => trim($this->input('message'))]);
        }
    }

    public function rules(): array
    {
        $rules = ['message' => ['required', 'string', 'max:1500']];
        foreach (self::SERVER_FIELDS as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (self::SERVER_FIELDS as $field) {
                if (array_key_exists($field, $this->all()) && ! $validator->errors()->has($field)) {
                    $validator->errors()->add($field, __('ai_chat.validation.unknown_field'));
                }
            }
            foreach (array_diff(array_keys($this->all()), ['message'], self::SERVER_FIELDS) as $field) {
                $validator->errors()->add($field, __('ai_chat.validation.unknown_field'));
            }
        }];
    }
}
