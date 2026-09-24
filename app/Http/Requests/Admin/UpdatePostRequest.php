<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends StorePostRequest
{
    public function rules(): array
    {
        /** @var Post $post */
        $post = $this->route('post');
        $rules = parent::rules();
        $rules['slug'] = [
            'required',
            'string',
            'max:200',
            'alpha_dash:ascii',
            Rule::unique('posts', 'slug')->ignore($post),
        ];
        $rules['remove_featured_image'] = ['nullable', 'boolean'];

        return $rules;
    }
}
