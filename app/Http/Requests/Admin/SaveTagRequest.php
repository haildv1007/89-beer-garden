<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') === true;
    }

    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash:ascii',
                Rule::unique('tags', 'slug')->ignore($tag instanceof Tag ? $tag : null),
            ],
            'icon_class' => ['nullable', 'string', 'max:100', 'regex:/^ti ti-[a-z0-9-]+$/'],
            'icon_color' => ['sometimes', 'required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background_color' => ['sometimes', 'required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'icon_class.regex' => 'Icon class phải có dạng “ti ti-tên-icon”.',
            'icon_color.regex' => 'Màu icon phải là mã màu HEX hợp lệ.',
            'background_color.regex' => 'Màu nền phải là mã màu HEX hợp lệ.',
        ];
    }
}
