<?php

use App\Http\Controllers\Kitchen\KitchenOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KitchenOrderController::class, 'index'])
    ->middleware('can:kitchen.queue.view')
    ->name('home');
Route::post('tickets/{kitchenTicket}/print', [KitchenOrderController::class, 'print'])
    ->middleware('can:kitchen.queue.view')
    ->name('tickets.print');
Route::get('tickets/{kitchenTicket}/printable', [KitchenOrderController::class, 'printable'])
    ->middleware('can:kitchen.queue.view')
    ->name('tickets.printable');
Route::get('autoprint', [KitchenOrderController::class, 'autoprint'])
    ->middleware('can:kitchen.queue.view')
    ->name('autoprint');
Route::get('autoprint/next', [KitchenOrderController::class, 'nextTicket'])
    ->middleware('can:kitchen.queue.view')
    ->name('autoprint.next');
Route::post('autoprint/tickets/{kitchenTicket}/complete', [KitchenOrderController::class, 'completeAutomaticPrint'])
    ->middleware('can:kitchen.queue.view')
    ->name('autoprint.complete');
Route::redirect('station', 'autoprint')->name('station');
