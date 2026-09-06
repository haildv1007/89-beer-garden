<?php

use App\Http\Controllers\Admin\BillingController as OperationalBillingController;
use App\Http\Controllers\Admin\DiningSessionController as OperationalDiningSessionController;
use App\Http\Controllers\Admin\FulfillmentOrderController as OperationalFulfillmentOrderController;
use App\Http\Controllers\POS\DiningSessionController;
use App\Http\Controllers\POS\OrderController;
use App\Http\Controllers\POS\OrderItemController;
use App\Http\Controllers\POS\ReservationController;
use App\Http\Controllers\POS\TableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TableController::class, 'index'])
    ->middleware('can:table.view')
    ->name('home');
Route::get('fulfillment-orders', [OperationalFulfillmentOrderController::class, 'index'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.index');
Route::get('fulfillment-orders/create', [OperationalFulfillmentOrderController::class, 'create'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.create');
Route::post('fulfillment-orders', [OperationalFulfillmentOrderController::class, 'store'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.store');
Route::get('fulfillment-orders/{fulfillmentOrder}', [OperationalFulfillmentOrderController::class, 'show'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.show');
Route::put('fulfillment-orders/{fulfillmentOrder}', [OperationalFulfillmentOrderController::class, 'update'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.update');
Route::post('fulfillment-orders/{fulfillmentOrder}/payment', [
    OperationalFulfillmentOrderController::class,
    'completePayment',
])
    ->middleware('can:payment.complete')
    ->name('fulfillment-orders.payment.complete');
Route::get('fulfillment-orders/{fulfillmentOrder}/invoice', [OperationalFulfillmentOrderController::class, 'invoice'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.invoice');
Route::patch('fulfillment-orders/{fulfillmentOrder}/confirm', [OperationalFulfillmentOrderController::class, 'confirm'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.confirm');
Route::patch('fulfillment-orders/{fulfillmentOrder}/reject', [OperationalFulfillmentOrderController::class, 'reject'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.reject');
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

Route::get('dining-sessions', [OperationalDiningSessionController::class, 'index'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.index');
Route::get('dining-sessions/{diningSession}', [OperationalDiningSessionController::class, 'show'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.show');
Route::put('dining-sessions/{diningSession}', [OperationalDiningSessionController::class, 'update'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.update');
Route::put('dining-sessions/{diningSession}/manage', [OperationalDiningSessionController::class, 'manage'])
    ->middleware(['can:dining-session.view', 'can:order.update'])
    ->name('dining-sessions.manage');
Route::post('dining-sessions/{diningSession}/billing', [OperationalDiningSessionController::class, 'openBilling'])
    ->middleware('can:billing.view')
    ->name('billing.open');
Route::get('bills/{bill}', [OperationalBillingController::class, 'show'])
    ->middleware('can:billing.view')
    ->name('bills.show');
Route::post('bills/{bill}/voucher', [OperationalBillingController::class, 'applyVoucher'])
    ->middleware(['can:billing.view', 'can:voucher.apply'])
    ->name('bills.voucher.apply');
Route::delete('bills/{bill}/voucher', [OperationalBillingController::class, 'removeVoucher'])
    ->middleware(['can:billing.view', 'can:voucher.apply'])
    ->name('bills.voucher.remove');
Route::post('bills/{bill}/payments/complete', [OperationalBillingController::class, 'complete'])
    ->middleware(['can:billing.view', 'can:payment.complete'])
    ->name('bills.payments.complete');
Route::post('bills/{bill}/payments/fail', [OperationalBillingController::class, 'fail'])
    ->middleware(['can:billing.view', 'can:payment.complete'])
    ->name('bills.payments.fail');
Route::get('bills/{bill}/invoice', [OperationalBillingController::class, 'invoice'])
    ->middleware('can:billing.view')
    ->name('bills.invoice');
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
    ->middleware('can:order-item.mark-served')
    ->name('order-items.mark-served');
Route::patch('order-items/{orderItem}/cancel-waiting', [OrderItemController::class, 'cancelWaiting'])
    ->middleware('can:order-item.cancel-waiting')
    ->name('order-items.cancel-waiting');
Route::patch('order-items/{orderItem}/cancel-preparing', [OrderItemController::class, 'cancelPreparing'])
    ->middleware('can:order-item.cancel-preparing')
    ->name('order-items.cancel-preparing');

Route::get('reservations', [ReservationController::class, 'index'])
    ->middleware('can:reservation.manage')
    ->name('reservations.index');
Route::get('reservations/create', [ReservationController::class, 'create'])
    ->middleware('can:reservation.manage')
    ->name('reservations.create');
Route::post('reservations', [ReservationController::class, 'store'])
    ->middleware('can:reservation.manage')
    ->name('reservations.store');
Route::get('reservations/{reservation}', [ReservationController::class, 'show'])
    ->middleware('can:reservation.manage')
    ->name('reservations.show');
Route::put('reservations/{reservation}', [ReservationController::class, 'update'])
    ->middleware('can:reservation.manage')
    ->name('reservations.update');
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
