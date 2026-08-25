<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\DiningContextController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RegisteredCustomerController;
use App\Http\Controllers\Customer\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('customer.home');
Route::get('/menu', [MenuController::class, 'index'])->name('customer.menu.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('customer.products.show');
Route::get('/dining-context/{diningSession}', [DiningContextController::class, 'bind'])
    ->middleware(['signed', 'throttle:10,1'])->name('customer.dining-context.bind');
Route::get('/cart', [CartController::class, 'index'])->name('customer.cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('customer.cart.items.store');
Route::patch('/cart/items/{productId}', [CartController::class, 'update'])->whereNumber('productId')->name('customer.cart.items.update');
Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])->whereNumber('productId')->name('customer.cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('customer.cart.clear');
Route::post('/cart/submit', [CartController::class, 'submit'])->middleware('throttle:10,1')->name('customer.cart.submit');
Route::get('/cart/success', [CartController::class, 'success'])->name('customer.cart.success');
Route::get('/orders/current', [CartController::class, 'status'])->name('customer.orders.current');
Route::get('/reservations/create', [ReservationController::class, 'create'])->name('customer.reservations.create');
Route::post('/reservations', [ReservationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('customer.reservations.store');
Route::get('/reservations/confirmation', [ReservationController::class, 'confirmation'])
    ->name('customer.reservations.confirmation');

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
    Route::get('/reservations', [ReservationController::class, 'index'])
        ->middleware('can:customer.reservation.view-own')
        ->name('customer.reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])
        ->middleware('can:customer.reservation.view-own')
        ->name('customer.reservations.show');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
