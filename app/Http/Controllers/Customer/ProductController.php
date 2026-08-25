<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CustomerOrder\CustomerDiningContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, CustomerDiningContextService $context): View
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
        ]);
    }
}
