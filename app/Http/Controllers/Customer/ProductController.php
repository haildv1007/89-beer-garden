<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerOrderingCapability;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(
        Product $product,
        CustomerOrderingCapability $ordering,
        DynamicTranslationResolver $resolver,
    ): View {
        abort_unless($product->status === Product::STATUS_ACTIVE && $product->category()->active()->exists(), 404);

        $product->load(['category', 'media']);

        $relatedProducts = Product::query()
            ->publicMenu()
            ->with(['category', 'media'])
            ->whereKeyNot($product->getKey())
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $relatedTranslations = $resolver->batch(
            $relatedProducts
                ->concat($relatedProducts->pluck('category'))
                ->unique(fn ($entity) => $entity::class.':'.$entity->id),
        );

        return view('customer.products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'relatedTranslations' => $relatedTranslations,
            'customerOrderingAvailable' => $ordering->enabled(),
            'translatedName' => $resolver->resolve($product, 'name'),
            'translatedShortDescription' => $resolver->resolve($product, 'short_description') ?: $resolver->resolve($product, 'description'),
            'translatedDescription' => $resolver->resolve($product, 'description'),
            'translatedCategoryName' => $resolver->resolve($product->category, 'name'),
        ]);
    }
}
