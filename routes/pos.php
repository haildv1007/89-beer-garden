<?php

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
