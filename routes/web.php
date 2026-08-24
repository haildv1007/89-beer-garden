<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('customer.home');
Route::get('/menu', [MenuController::class, 'index'])->name('customer.menu.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('customer.products.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
