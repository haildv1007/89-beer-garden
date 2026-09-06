<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Customer\CartCheckoutController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\LocaleController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderHistoryController;
use App\Http\Controllers\Customer\PostController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ProductMediaController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RegisteredCustomerController;
use App\Http\Controllers\Customer\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('customer.home');
Route::get('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', ['vi', 'en', 'zh'])
    ->name('locale.switch');
Route::get('/menu', [MenuController::class, 'index'])->name('customer.menu.index');
Route::get('/tin-tuc', [PostController::class, 'index'])->name('customer.posts.index');
Route::get('/tin-tuc/{post:slug}', [PostController::class, 'show'])->name('customer.posts.show');
Route::get('/product-media/{productMedia}', ProductMediaController::class)
    ->whereNumber('productMedia')
    ->name('customer.product-media.show');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('customer.products.show');
Route::get('/cart', [CartController::class, 'index'])->name('customer.cart.index');
Route::get('/cart/mini', [CartController::class, 'mini'])->name('customer.cart.mini');
Route::post('/cart/items', [CartController::class, 'store'])->name('customer.cart.items.store');
Route::patch('/cart/items/{productId}', [CartController::class, 'update'])
    ->whereNumber('productId')
    ->name('customer.cart.items.update');
Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])
    ->whereNumber('productId')
    ->name('customer.cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('customer.cart.clear');
Route::post('/cart/fulfillment', [CartController::class, 'selectFulfillment'])->name(
    'customer.cart.fulfillment.select',
);
Route::post('/cart/voucher', [CartController::class, 'applyVoucher'])->name('customer.cart.voucher.apply');
Route::delete('/cart/voucher', [CartController::class, 'removeVoucher'])->name('customer.cart.voucher.remove');
Route::get('/cart/checkout', [CartCheckoutController::class, 'show'])->name('customer.cart.checkout');
Route::post('/cart/checkout', [CartCheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('customer.cart.checkout.store');
Route::get('/cart/checkout/success', [CartCheckoutController::class, 'success'])->name(
    'customer.cart.checkout.success',
);
Route::get('/cart/payment/{fulfillmentOrder}', [CartCheckoutController::class, 'payment'])
    ->middleware('signed')
    ->name('customer.cart.external-payment');
Route::post('/cart/payment/{fulfillmentOrder}/reported', [CartCheckoutController::class, 'reportPayment'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('customer.cart.external-payment.report');
Route::get('/cart/delivery-checkout', [CartCheckoutController::class, 'showDelivery'])->name(
    'customer.cart.delivery-checkout',
);
Route::post('/cart/delivery-checkout', [CartCheckoutController::class, 'storeDelivery'])
    ->middleware('throttle:10,1')
    ->name('customer.cart.delivery-checkout.store');
Route::get('/cart/delivery-checkout/success', [CartCheckoutController::class, 'deliverySuccess'])->name(
    'customer.cart.delivery-checkout.success',
);
Route::get('/reservations/create', [ReservationController::class, 'create'])->name('customer.reservations.create');
Route::post('/reservations', [ReservationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('customer.reservations.store');
Route::get('/reservations/confirmation', [ReservationController::class, 'confirmation'])->name(
    'customer.reservations.confirmation',
);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');
    Route::get('/register', [RegisteredCustomerController::class, 'create'])->name('customer.registration.create');
    Route::post('/register', [RegisteredCustomerController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('customer.registration.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/orders/history', [OrderHistoryController::class, 'index'])
        ->middleware('can:customer.order.view-own')
        ->name('customer.orders.history');
    Route::get('/orders/history/{diningSession}', [OrderHistoryController::class, 'show'])
        ->middleware('can:customer.order.view-own')
        ->name('customer.orders.history.show');
    Route::get('/profile/{legacyCustomer?}', [ProfileController::class, 'show'])
        ->whereNumber('legacyCustomer')
        ->name('customer.profile.show');
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
