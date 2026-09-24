<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.view') === true;
    }

    public function rules(): array
    {
        $linkedUserId = $this->route('customer')?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^0\d{9}$/',
                'max:30',
                Rule::unique('users', 'phone')->ignore($linkedUserId),
            ],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($linkedUserId)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('phone')) {
                    return;
                }
                $duplicate = Customer::query()
                    ->whereKeyNot($this->route('customer')->id)
                    ->whereIn('phone', PhoneNumber::variants($this->string('phone')->toString()))
                    ->exists();
                if ($duplicate) {
                    $validator->errors()->add('phone', 'Số điện thoại này đang thuộc một hồ sơ khách hàng khác.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'phone' => PhoneNumber::normalize($this->phone),
            'email' => ($email = mb_strtolower(trim((string) $this->email))) === '' ? null : $email,
        ]);
    }
}
