<?php

namespace App\Services\Order;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Billing\RefreshOpenBillService;
use App\Services\BusinessCode\BusinessCodeGenerator;
use App\Services\Kitchen\KitchenTicketService;
use App\Services\Menu\ProductVariantSelectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    public function __construct(
        private readonly RefreshOpenBillService $billRefresher,
        private readonly KitchenTicketService $kitchenTickets,
        private readonly BusinessCodeGenerator $codes,
        private readonly ProductVariantSelectionService $variants,
    ) {}

    /** @param list<array{product_id:int, quantity:int, note?:string|null}> $items */
    public function create(DiningSession $session, User $actor, array $items, ?string $note): Order
    {
        return $this->createCore($session, $items, $note, $actor);
    }

    /** @param list<array{product_id:int, quantity:int, note?:string|null}> $items */
    private function createCore(
        DiningSession $session,
        array $items,
        ?string $note,
        User $actor,
    ): Order {
        return DB::transaction(function () use ($session, $items, $note, $actor): Order {
            $lockedSession = DiningSession::query()->lockForUpdate()->findOrFail($session->id);
            $employee = Employee::query()
                ->where('user_id', $actor->id)
                ->where('status', EmployeeStatus::Active->value)
                ->lockForUpdate()
                ->firstOrFail();
            $table = RestaurantTable::query()->lockForUpdate()->findOrFail($lockedSession->table_id);

            if (
                $lockedSession->status !== DiningSessionStatus::Active ||
                ! $table->is_active ||
                $table->runtime_status !== RestaurantTableStatus::Occupied ||
                $table->activeDiningSession()->whereKey($lockedSession->id)->doesntExist()
            ) {
                throw ValidationException::withMessages(['dining_session' => __('order.errors.session_invalid')]);
            }

            $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->with(['category:id,status', 'variants'])
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== $productIds->unique()->count()) {
                throw ValidationException::withMessages(['items' => __('order.errors.product_invalid')]);
            }

            $order = Order::query()->forceCreate([
                'order_code' => $this->codes->next(BusinessCodeGenerator::DINING_ORDER),
                'dining_session_id' => $lockedSession->id,
                'created_by_employee_id' => $employee?->id,
                'created_by_customer_id' => null,
                'source' => 'staff',
                'note' => $note,
                'ordered_at' => now(),
            ]);

            foreach ($items as $input) {
                $product = $products->get((int) $input['product_id']);
                $variant = $this->variants->resolve($product, isset($input['variant_id']) ? (int) $input['variant_id'] : null);
                $unitPrice = $this->variants->price($product, $variant);
                $quantity = (int) $input['quantity'];
                if (
                    $product->status !== Product::STATUS_ACTIVE ||
                    ! $product->is_available ||
                    $product->category?->status !== 'active' ||
                    $unitPrice > intdiv(PHP_INT_MAX, $quantity)
                ) {
                    throw ValidationException::withMessages(['items' => __('order.errors.product_invalid')]);
                }
                OrderItem::query()->forceCreate([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                    'status' => OrderItemStatus::Waiting,
                    'note' => $input['note'] ?? null,
                ]);
            }

            $this->billRefresher->refreshForLockedSession($lockedSession->id);

            $this->kitchenTickets->createForOrder($order, $employee);

            return $order->load('items');
        });
    }
}
