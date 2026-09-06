<?php

namespace App\Http\Requests\Customer;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^(?:\+?84|0)\d{9}$/', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role_id' => ['prohibited'],
            'status' => ['prohibited'],
            'user_id' => ['prohibited'],
            'note' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $email = mb_strtolower(trim($this->email));
            $this->merge(['email' => $email === '' ? null : $email]);
        }
        if (is_string($this->name)) {
            $name = trim($this->name);
            $this->merge(['name' => $name === '' ? null : $name]);
        }
        if (is_string($this->phone)) {
            $this->merge(['phone' => PhoneNumber::normalize($this->phone)]);
        }
    }
}
