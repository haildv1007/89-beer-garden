<?php

use App\Http\Controllers\POS\DiningSessionController;
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
