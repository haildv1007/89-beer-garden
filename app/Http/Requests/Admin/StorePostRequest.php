<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('post.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('title'))),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', 'alpha_dash:ascii', Rule::unique('posts', 'slug')],
            'category' => ['required', 'string', Rule::exists('post_categories', 'slug')],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:50000'],
            'content_format' => ['sometimes', Rule::in(['text', 'html'])],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['required', Rule::in([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED])],
            'is_featured' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
