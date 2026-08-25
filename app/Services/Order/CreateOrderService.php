<?php

namespace App\Services\Order;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\CustomerOrder\CustomerOrderingCapability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    public function __construct(private readonly CustomerOrderingCapability $customerOrdering) {}

    /** @param list<array{product_id:int, quantity:int, note?:string|null}> $items */
    public function create(DiningSession $session, User $actor, array $items, ?string $note): Order
    {
        return $this->createCore($session, $items, $note, 'staff', $actor);
    }

    /** @param list<array{product_id:int, quantity:int, note?:string|null}> $items */
    public function createForCustomer(DiningSession $session, ?User $actor, array $items, ?string $note): Order
    {
        return $this->createCore($session, $items, $note, 'customer', $actor);
    }

    /** @param list<array{product_id:int, quantity:int, note?:string|null}> $items */
    private function createCore(DiningSession $session, array $items, ?string $note, string $source, ?User $actor): Order
    {
        return DB::transaction(function () use ($session, $items, $note, $source, $actor): Order {
            $lockedSession = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($source === 'customer' && ! $this->customerOrdering->enabled(lockForUpdate: true)) {
                throw ValidationException::withMessages(['context' => __('customer_order.errors.disabled')]);
            }
            $employee = null;
            if ($source === 'staff') {
                $employee = Employee::query()->where('user_id', $actor?->id)
                    ->where('status', EmployeeStatus::Active->value)->lockForUpdate()->firstOrFail();
            }
            $table = RestaurantTable::query()->lockForUpdate()->findOrFail($lockedSession->table_id);

            if ($lockedSession->status !== DiningSessionStatus::Active
                || ! $table->is_active || $table->runtime_status !== RestaurantTableStatus::Occupied
                || $table->activeDiningSession()->whereKey($lockedSession->id)->doesntExist()) {
                throw ValidationException::withMessages(['dining_session' => __('order.errors.session_invalid')]);
            }

            $customer = null;
            if ($source === 'customer' && $actor !== null) {
                $customer = Customer::query()->where('user_id', $actor->id)->lockForUpdate()->firstOrFail();
            }

            $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()->with('category:id,status')->whereIn('id', $productIds)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => __('order.errors.product_invalid')]);
            }

            $order = Order::query()->forceCreate([
                'order_code' => 'ORD-'.Str::ulid(), 'dining_session_id' => $lockedSession->id,
                'created_by_employee_id' => $employee?->id, 'created_by_customer_id' => $customer?->id,
                'source' => $source, 'note' => $note, 'ordered_at' => now(),
            ]);

            foreach ($items as $input) {
                $product = $products->get((int) $input['product_id']);
                $quantity = (int) $input['quantity'];
                if ($product->status !== Product::STATUS_ACTIVE || ! $product->is_available
                    || $product->category?->status !== 'active' || $product->price > intdiv(PHP_INT_MAX, $quantity)) {
                    throw ValidationException::withMessages(['items' => __('order.errors.product_invalid')]);
                }
                OrderItem::query()->forceCreate([
                    'order_id' => $order->id, 'product_id' => $product->id,
                    'product_name' => $product->name, 'quantity' => $quantity,
                    'unit_price' => $product->price, 'line_total' => $product->price * $quantity,
                    'status' => OrderItemStatus::Waiting, 'note' => $input['note'] ?? null,
                ]);
            }

            return $order->load('items');
        });
    }
}
