<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerDiningContextService;
use App\Services\Translation\DynamicTranslationResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, CustomerDiningContextService $context, DynamicTranslationResolver $resolver): View
    {
        abort_unless(
            $product->status === Product::STATUS_ACTIVE
                && $product->category()->active()->exists(),
            404,
        );

        $product->load('category');

        return view('customer.products.show', [
            'product' => $product,
            'customerOrderingAvailable' => $context->available($request),
            'translatedName' => $resolver->resolve($product, 'name'), 'translatedDescription' => $resolver->resolve($product, 'description'),
            'translatedCategoryName' => $resolver->resolve($product->category, 'name'),
        ]);
    }
}
