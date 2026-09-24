<?php

namespace App\Services\Menu;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class ProductVariantSelectionService
{
    public function resolve(Product $product, ?int $variantId, bool $allowDefault = false): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : $product->variants()->get();
        if ($variants->isEmpty()) {
            if ($variantId !== null) {
                $this->invalid();
            }

            return null;
        }

        $variant = $variantId === null && $allowDefault
            ? $variants->where('is_available', true)->sortBy('sort_order')->first()
            : $variants->firstWhere('id', $variantId);
        if (! $variant instanceof ProductVariant || $variant->trashed() || ! $variant->is_available) {
            $this->invalid();
        }

        return $variant;
    }

    public function price(Product $product, ?ProductVariant $variant): int
    {
        return $variant?->price ?? $product->price;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['variant_id' => 'Vui lòng chọn một biến thể đang được phục vụ.']);
    }
}
