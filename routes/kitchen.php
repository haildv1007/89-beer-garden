<?php

use App\Http\Controllers\Kitchen\KitchenOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KitchenOrderController::class, 'index'])
    ->middleware('can:kitchen.queue.view')->name('home');
Route::patch('order-items/{orderItem}/start-preparing', [KitchenOrderController::class, 'startPreparing'])
    ->middleware('can:order-item.mark-preparing')->name('order-items.start-preparing');
Route::patch('order-items/{orderItem}/mark-ready', [KitchenOrderController::class, 'markReady'])
    ->middleware('can:order-item.mark-ready')->name('order-items.mark-ready');
