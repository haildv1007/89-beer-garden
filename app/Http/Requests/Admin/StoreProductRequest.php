<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') === true && $this->user()?->can('product.update-price') === true;
    }

    public function rules(): array
    {
        return $this->baseRules() + ['price' => ['required', 'integer', 'min:0']];
    }

    protected function baseRules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('products', 'slug')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'distinct', Rule::exists('tags', 'id')],
            'variants_present' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array', 'max:20'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required_with:variants.*.price', 'nullable', 'string', 'max:100'],
            'variants.*.price' => ['required_with:variants.*.name', 'nullable', 'integer', 'min:0'],
            'variants.*.is_available' => ['nullable', 'boolean'],
            'media_slots' => ['nullable', 'array', 'max:5'],
            'media_slots.*' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm',
                'max:5120',
            ],
            'media_order' => ['nullable', 'array', 'max:5'],
            'media_order.*' => ['nullable', 'integer', 'distinct'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
            'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->file('media_slots', [])) as $position) {
                    if (! in_array((int) $position, range(1, 5), true)) {
                        $validator->errors()->add('media_slots', 'Vị trí media chỉ được nằm trong khoảng từ 1 đến 5.');
                    }
                }

                $product = $this->route('product');
                if ($this->filled('variants_present') && $this->user()?->can('product.update-price') !== true) {
                    $validator->errors()->add('variants', 'Bạn không có quyền thay đổi biến thể và giá bán.');
                }
                if ($product instanceof Product) {
                    $requestedIds = collect($this->input('media_order', []))->filter()->map(fn ($id): int => (int) $id);
                    if (
                        $requestedIds->isNotEmpty() &&
                        $product->media()->whereKey($requestedIds)->count() !== $requestedIds->count()
                    ) {
                        $validator->errors()->add('media_order', 'Thứ tự media không hợp lệ.');
                    }
                    $variantIds = collect($this->input('variants', []))->pluck('id')->filter()->map(fn ($id): int => (int) $id);
                    if ($variantIds->isNotEmpty() && $product->variants()->whereKey($variantIds)->count() !== $variantIds->count()) {
                        $validator->errors()->add('variants', 'Biến thể không hợp lệ.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'media_slots.*.uploaded' => 'Không thể tải tệp lên. Hãy kiểm tra dung lượng tệp và giới hạn upload của máy chủ.',
            'media_slots.*.file' => 'Media phải là một tệp hợp lệ.',
            'media_slots.*.mimetypes' => 'Chỉ hỗ trợ ảnh JPG, PNG, WebP hoặc video MP4, WebM.',
            'media_slots.*.max' => 'Mỗi ảnh hoặc video không được lớn hơn 5 MB.',
        ];
    }
}
