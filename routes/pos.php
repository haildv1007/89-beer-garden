<?php

use App\Http\Controllers\POS\DiningSessionController;
use App\Http\Controllers\POS\OrderController;
use App\Http\Controllers\POS\OrderItemController;
use App\Http\Controllers\POS\ReservationController;
use App\Http\Controllers\POS\TableController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pos.home')->name('home');
Route::get('tables', [TableController::class, 'index'])
    ->middleware('can:table.view')
    ->name('tables.index');
Route::patch('tables/{restaurantTable}/available', [TableController::class, 'markAvailable'])
    ->middleware('can:table.operate')
    ->name('tables.mark-available');
Route::get('tables/{restaurantTable}/dining-sessions/create', [DiningSessionController::class, 'create'])
    ->middleware(['can:dining-session.open', 'can:table.operate'])
    ->name('dining-sessions.create');
Route::post('tables/{restaurantTable}/dining-sessions', [DiningSessionController::class, 'store'])
    ->middleware(['can:dining-session.open', 'can:table.operate'])
    ->name('dining-sessions.store');

Route::get('dining-sessions', [DiningSessionController::class, 'index'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.index');
Route::get('dining-sessions/{diningSession}', [DiningSessionController::class, 'show'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.show');
Route::get('dining-sessions/{diningSession}/orders/create', [OrderController::class, 'create'])
    ->middleware(['can:dining-session.view', 'can:order.create'])
    ->name('orders.create');
Route::post('dining-sessions/{diningSession}/orders', [OrderController::class, 'store'])
    ->middleware(['can:dining-session.view', 'can:order.create'])
    ->name('orders.store');
Route::patch('order-items/{orderItem}', [OrderController::class, 'updateItem'])
    ->middleware(['can:dining-session.view', 'can:order.update'])
    ->name('order-items.update');
Route::patch('order-items/{orderItem}/served', [OrderItemController::class, 'markServed'])
    ->middleware('can:order-item.mark-served')->name('order-items.mark-served');
Route::patch('order-items/{orderItem}/cancel-waiting', [OrderItemController::class, 'cancelWaiting'])
    ->middleware('can:order-item.cancel-waiting')->name('order-items.cancel-waiting');
Route::patch('order-items/{orderItem}/cancel-preparing', [OrderItemController::class, 'cancelPreparing'])
    ->middleware('can:order-item.cancel-preparing')->name('order-items.cancel-preparing');

Route::get('reservations', [ReservationController::class, 'index'])
    ->middleware('can:reservation.manage')
    ->name('reservations.index');
Route::get('reservations/{reservation}', [ReservationController::class, 'show'])
    ->middleware('can:reservation.manage')
    ->name('reservations.show');
Route::patch('reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])
    ->middleware('can:reservation.manage')
    ->name('reservations.confirm');
Route::patch('reservations/{reservation}/reject', [ReservationController::class, 'reject'])
    ->middleware('can:reservation.manage')
    ->name('reservations.reject');
Route::patch('reservations/{reservation}/no-show', [ReservationController::class, 'markNoShow'])
    ->middleware('can:reservation.mark-no-show')
    ->name('reservations.mark-no-show');
Route::post('reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
    ->middleware(['can:reservation.manage', 'can:dining-session.open', 'can:table.operate'])
    ->name('reservations.check-in');
