<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DiningSessionController;
use App\Http\Controllers\Admin\EmployeeAccountController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeStatusController;
use App\Http\Controllers\Admin\FulfillmentOrderController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PostImageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RestaurantTableController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\POS\DiningSessionController as DiningSessionOperationsController;
use App\Http\Controllers\POS\OrderController as OrderOperationsController;
use App\Http\Controllers\POS\OrderItemController as OrderItemOperationsController;
use App\Http\Controllers\POS\ReservationController as ReservationOperationsController;
use App\Http\Controllers\POS\TableController as TableOperationsController;
use App\Services\SystemSetting\SystemSettingCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', [RestaurantTableController::class, 'index'])
    ->middleware('can:restaurant-table.manage')
    ->name('home');

Route::get('table-map', [TableOperationsController::class, 'index'])
    ->middleware('can:table.view')
    ->name('tables.index');
Route::patch('table-map/{restaurantTable}/available', [TableOperationsController::class, 'markAvailable'])
    ->middleware('can:table.operate')
    ->name('tables.mark-available');
Route::get('table-map/{restaurantTable}/dining-sessions/create', [DiningSessionOperationsController::class, 'create'])
    ->middleware(['can:dining-session.open', 'can:table.operate'])
    ->name('dining-sessions.create');
Route::post('table-map/{restaurantTable}/dining-sessions', [DiningSessionOperationsController::class, 'store'])
    ->middleware(['can:dining-session.open', 'can:table.operate'])
    ->name('dining-sessions.store');

Route::get('reservations', [ReservationOperationsController::class, 'index'])
    ->middleware('can:reservation.manage')
    ->name('reservations.index');
Route::get('reservations/create', [ReservationOperationsController::class, 'create'])
    ->middleware('can:reservation.manage')
    ->name('reservations.create');
Route::post('reservations', [ReservationOperationsController::class, 'store'])
    ->middleware('can:reservation.manage')
    ->name('reservations.store');
Route::get('reservations/{reservation}', [ReservationOperationsController::class, 'show'])
    ->middleware('can:reservation.manage')
    ->name('reservations.show');
Route::put('reservations/{reservation}', [ReservationOperationsController::class, 'update'])
    ->middleware('can:reservation.manage')
    ->name('reservations.update');
Route::patch('reservations/{reservation}/confirm', [ReservationOperationsController::class, 'confirm'])
    ->middleware('can:reservation.manage')
    ->name('reservations.confirm');
Route::patch('reservations/{reservation}/reject', [ReservationOperationsController::class, 'reject'])
    ->middleware('can:reservation.manage')
    ->name('reservations.reject');
Route::patch('reservations/{reservation}/no-show', [ReservationOperationsController::class, 'markNoShow'])
    ->middleware(['can:reservation.manage', 'can:reservation.mark-no-show'])
    ->name('reservations.mark-no-show');
Route::post('reservations/{reservation}/check-in', [ReservationOperationsController::class, 'checkIn'])
    ->middleware(['can:reservation.manage', 'can:dining-session.open', 'can:table.operate'])
    ->name('reservations.check-in');

Route::get('fulfillment-orders', [FulfillmentOrderController::class, 'index'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.index');
Route::get('fulfillment-orders/create', [FulfillmentOrderController::class, 'create'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.create');
Route::post('fulfillment-orders', [FulfillmentOrderController::class, 'store'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.store');
Route::get('fulfillment-orders/{fulfillmentOrder}', [FulfillmentOrderController::class, 'show'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.show');
Route::put('fulfillment-orders/{fulfillmentOrder}', [FulfillmentOrderController::class, 'update'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.update');
Route::post('fulfillment-orders/{fulfillmentOrder}/payment', [FulfillmentOrderController::class, 'completePayment'])
    ->middleware('can:payment.complete')
    ->name('fulfillment-orders.payment.complete');
Route::get('fulfillment-orders/{fulfillmentOrder}/invoice', [FulfillmentOrderController::class, 'invoice'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.invoice');
Route::patch('fulfillment-orders/{fulfillmentOrder}/confirm', [FulfillmentOrderController::class, 'confirm'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.confirm');
Route::patch('fulfillment-orders/{fulfillmentOrder}/reject', [FulfillmentOrderController::class, 'reject'])
    ->middleware('can:order.create')
    ->name('fulfillment-orders.reject');
Route::get('dining-sessions', [DiningSessionController::class, 'index'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.index');
Route::get('dining-sessions/{diningSession}', [DiningSessionController::class, 'show'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.show');
Route::put('dining-sessions/{diningSession}', [DiningSessionController::class, 'update'])
    ->middleware('can:dining-session.view')
    ->name('dining-sessions.update');
Route::put('dining-sessions/{diningSession}/manage', [DiningSessionController::class, 'manage'])
    ->middleware(['can:dining-session.view', 'can:order.update'])
    ->name('dining-sessions.manage');
Route::post('dining-sessions/{diningSession}/billing', [DiningSessionController::class, 'openBilling'])
    ->middleware('can:billing.view')
    ->name('dining-sessions.billing.open');
Route::get('bills/{bill}', [BillingController::class, 'show'])
    ->middleware('can:billing.view')
    ->name('bills.show');
Route::post('bills/{bill}/voucher', [BillingController::class, 'applyVoucher'])
    ->middleware(['can:billing.view', 'can:voucher.apply'])
    ->name('bills.voucher.apply');
Route::delete('bills/{bill}/voucher', [BillingController::class, 'removeVoucher'])
    ->middleware(['can:billing.view', 'can:voucher.apply'])
    ->name('bills.voucher.remove');
Route::post('bills/{bill}/payments/complete', [BillingController::class, 'complete'])
    ->middleware(['can:billing.view', 'can:payment.complete'])
    ->name('bills.payments.complete');
Route::post('bills/{bill}/payments/fail', [BillingController::class, 'fail'])
    ->middleware(['can:billing.view', 'can:payment.complete'])
    ->name('bills.payments.fail');
Route::get('bills/{bill}/invoice', [BillingController::class, 'invoice'])
    ->middleware('can:billing.view')
    ->name('bills.invoice');
Route::get('dining-sessions/{diningSession}/orders/create', [OrderOperationsController::class, 'create'])
    ->middleware('can:order.create')
    ->name('dining-sessions.orders.create');
Route::post('dining-sessions/{diningSession}/orders', [OrderOperationsController::class, 'store'])
    ->middleware('can:order.create')
    ->name('dining-sessions.orders.store');
Route::patch('order-items/{orderItem}', [OrderOperationsController::class, 'updateItem'])
    ->middleware(['can:dining-session.view', 'can:order.update'])
    ->name('order-items.update');
Route::patch('order-items/{orderItem}/served', [OrderItemOperationsController::class, 'markServed'])
    ->middleware('can:order-item.mark-served')
    ->name('order-items.mark-served');
Route::patch('order-items/{orderItem}/cancel-waiting', [OrderItemOperationsController::class, 'cancelWaiting'])
    ->middleware('can:order-item.cancel-waiting')
    ->name('order-items.cancel-waiting');
Route::patch('order-items/{orderItem}/cancel-preparing', [OrderItemOperationsController::class, 'cancelPreparing'])
    ->middleware('can:order-item.cancel-preparing')
    ->name('order-items.cancel-preparing');

Route::get('reports', [ReportController::class, 'index'])
    ->middleware('can:report.view')
    ->name('reports.index');

Route::get('settings', [SystemSettingController::class, 'index'])
    ->middleware('can:settings.update')
    ->name('settings.index');
Route::put('settings/group/{group}', [SystemSettingController::class, 'updateGroup'])
    ->whereIn('group', array_keys(app(SystemSettingCatalog::class)->groups()))
    ->middleware('can:settings.update')
    ->name('settings.group.update');
Route::put('settings/{key}', [SystemSettingController::class, 'update'])
    ->whereIn('key', array_keys(app(SystemSettingCatalog::class)->definitions()))
    ->middleware('can:settings.update')
    ->name('settings.update');
Route::resource('categories', CategoryController::class)->middleware('can:category.manage');

Route::get('products/create', [ProductController::class, 'create'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.create');
Route::post('products', [ProductController::class, 'store'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.store');
Route::resource('products', ProductController::class)
    ->except(['create', 'store'])
    ->middleware('can:product.manage');
Route::patch('products/{product}/price', [ProductController::class, 'updatePrice'])
    ->middleware(['can:product.manage', 'can:product.update-price'])
    ->name('products.price.update');

Route::resource('posts', PostController::class)
    ->except('show')
    ->middleware('can:post.manage');

Route::resource('post-categories', PostCategoryController::class)
    ->except('show')->middleware('can:post.manage');
Route::post('post-images', [PostImageController::class, 'store'])
    ->name('post-images.store')->middleware('can:post.manage');

Route::resource('employees', EmployeeController::class)
    ->only(['index', 'show', 'create', 'store', 'edit', 'update'])
    ->middleware('can:employee.manage');
Route::post('employees/{employee}/account', [EmployeeAccountController::class, 'store'])
    ->middleware(['can:employee.manage', 'can:permission.assign'])
    ->name('employees.accounts.store');
Route::patch('employees/{employee}/role', [EmployeeAccountController::class, 'update'])
    ->middleware('can:permission.assign')
    ->name('employees.roles.update');
Route::patch('employees/{employee}/disable', [EmployeeStatusController::class, 'destroy'])
    ->middleware('can:employee.disable')
    ->name('employees.disable');

Route::get('roles', [RoleController::class, 'index'])
    ->middleware('can:permission.assign')
    ->name('roles.index');
Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
    ->middleware('can:permission.assign')
    ->name('roles.permissions.update');

Route::resource('restaurant-tables', RestaurantTableController::class)->middleware('can:restaurant-table.manage');

Route::resource('customers', CustomerController::class)
    ->only(['index', 'show', 'edit', 'update', 'destroy'])
    ->middleware('can:customer.view');
Route::post('customers/{customer}/merge', [CustomerController::class, 'merge'])
    ->middleware('can:customer.view')
    ->name('customers.merge');

Route::resource('vouchers', VoucherController::class)->except('show')->middleware('can:voucher.manage');

if (config('features.inventory')) {
    Route::get('inventory-items/{inventoryItem}/movements/create', [InventoryController::class, 'createMovement'])
        ->middleware(['can:inventory.view', 'can:inventory.stock-movement.create'])
        ->name('inventory-items.movements.create');
    Route::post('inventory-items/{inventoryItem}/movements', [InventoryController::class, 'storeMovement'])
        ->middleware(['can:inventory.view', 'can:inventory.stock-movement.create'])
        ->name('inventory-items.movements.store');
    Route::resource('inventory-items', InventoryController::class)
        ->except('destroy')
        ->middleware('can:inventory.view');
}
