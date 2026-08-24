<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RegisteredCustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('customer.home');
Route::get('/menu', [MenuController::class, 'index'])->name('customer.menu.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('customer.products.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredCustomerController::class, 'create'])
        ->name('customer.registration.create');
    Route::post('/register', [RegisteredCustomerController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('customer.registration.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/{customer}', [ProfileController::class, 'show'])->name('customer.profile.show');
    Route::get('/profile/{customer}/edit', [ProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile/{customer}', [ProfileController::class, 'update'])->name('customer.profile.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
