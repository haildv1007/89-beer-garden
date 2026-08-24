<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'admin.home')->name('home');

Route::resource('categories', CategoryController::class)
    ->middleware('can:category.manage');

Route::get('products/create', [ProductController::class, 'create'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.create');
Route::post('products', [ProductController::class, 'store'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.store');
Route::resource('products', ProductController::class)
    ->except(['create', 'store'])
    ->middleware('can:product.manage');
Route::patch('products/{product}/price', [ProductController::class, 'updatePrice'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.price.update');
