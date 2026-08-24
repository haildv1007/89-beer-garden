<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless(
            $product->status === Product::STATUS_ACTIVE
                && $product->category()->active()->exists(),
            404,
        );

        $product->load('category');

        return view('customer.products.show', compact('product'));
    }
}
