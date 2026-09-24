<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_create_optional_product_item_at_zero_and_update_metadata_only(): void
    {
        $manager = $this->user('manager');
        $this->actingAs($manager)
            ->post(route('admin.inventory-items.store'), $this->itemPayload(' beer-01 ', null))
            ->assertRedirect();
        $item = InventoryItem::query()->sole();
        $this->assertSame('BEER-01', $item->sku);
        $this->assertSame(0, $item->current_stock);
        $this->assertNull($item->product_id);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->put(
            route('admin.inventory-items.update', $item),
            $this->itemPayload('BEER-02', null, 5, 'inactive'),
        )->assertRedirect();
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'sku' => 'BEER-02',
            'minimum_stock' => 5,
            'status' => 'inactive',
            'current_stock' => 0,
        ]);
    }

    public function test_sku_product_uniqueness_and_product_validity_are_enforced(): void
    {
        $manager = $this->user('manager');
        $product = $this->product('Linked');
        $this->actingAs($manager)->post(
            route('admin.inventory-items.store'),
            $this->itemPayload('SKU-1', $product->id),
        );
        $this->post(route('admin.inventory-items.store'), $this->itemPayload('sku-1', null))->assertSessionHasErrors(
            'sku',
        );
        $this->post(
            route('admin.inventory-items.store'),
            $this->itemPayload('SKU-2', $product->id),
        )->assertSessionHasErrors('product_id');
        $deleted = $this->product('Deleted');
        $deleted->delete();
        $this->post(
            route('admin.inventory-items.store'),
            $this->itemPayload('SKU-3', $deleted->id),
        )->assertSessionHasErrors('product_id');
        $inactive = $this->product('Inactive', Product::STATUS_INACTIVE);
        $this->post(
            route('admin.inventory-items.store'),
            $this->itemPayload('SKU-4', $inactive->id),
        )->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('inventory_items', 1);
    }

    public function test_current_stock_and_movement_audit_fields_are_server_owned(): void
    {
        $manager = $this->user('manager');
        $this->actingAs($manager)
            ->post(route('admin.inventory-items.store'), $this->itemPayload('FORGED', null) + ['current_stock' => 999])
            ->assertSessionHasErrors('current_stock');
        $item = $this->item('AUDIT', 10);
        $payload = [
            'type' => 'import',
            'quantity' => 5,
            'note' => '  Delivery  ',
            'stock_before' => 99,
            'stock_after' => 999,
            'created_by_employee_id' => 999,
            'created_at' => now(),
            'current_stock' => 999,
        ];
        $this->post(route('admin.inventory-items.movements.store', $item), $payload)->assertSessionHasErrors([
            'stock_before',
            'stock_after',
            'created_by_employee_id',
            'created_at',
            'current_stock',
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->post(route('admin.inventory-items.movements.store', $item), [
            'type' => 'import',
            'quantity' => 5,
            'note' => '  Delivery  ',
        ]);
        $movement = StockMovement::query()->sole();
        $this->assertSame([10, 15, 5], [$movement->stock_before, $movement->stock_after, $movement->quantity]);
        $this->assertSame($manager->employee->id, $movement->created_by_employee_id);
        $this->assertSame('Delivery', $movement->note);
        $this->assertNotNull($movement->created_at);
    }

    public function test_all_increase_and_decrease_types_create_a_valid_audit_chain(): void
    {
        $manager = $this->user('manager');
        $item = $this->item('TYPES', 20);
        $expected = 20;
        $this->actingAs($manager);
        foreach ([StockMovementType::Import, StockMovementType::AdjustmentIn, StockMovementType::Return] as $type) {
            $this->post(route('admin.inventory-items.movements.store', $item), [
                'type' => $type->value,
                'quantity' => 3,
            ])->assertRedirect();
            $expected += 3;
            $this->assertSame($expected, $item->fresh()->current_stock);
        }
        foreach ([StockMovementType::Export, StockMovementType::AdjustmentOut, StockMovementType::Damaged] as $type) {
            $this->post(route('admin.inventory-items.movements.store', $item), [
                'type' => $type->value,
                'quantity' => 2,
            ])->assertRedirect();
            $expected -= 2;
            $this->assertSame($expected, $item->fresh()->current_stock);
        }
        $movements = StockMovement::query()->orderBy('id')->get();
        $this->assertCount(6, $movements);
        $this->assertSame(20, $movements->first()->stock_before);
        for ($index = 1; $index < $movements->count(); $index++) {
            $this->assertSame($movements[$index - 1]->stock_after, $movements[$index]->stock_before);
        }
    }

    public function test_invalid_quantity_insufficient_stock_and_overflow_are_rejected(): void
    {
        $manager = $this->user('manager');
        $item = $this->item('LIMITS', 5);
        $this->actingAs($manager);
        foreach ([0, -1, 1.5, 'five'] as $quantity) {
            $this->post(route('admin.inventory-items.movements.store', $item), [
                'type' => 'import',
                'quantity' => $quantity,
            ])->assertSessionHasErrors('quantity');
        }
        $this->post(route('admin.inventory-items.movements.store', $item), [
            'type' => 'export',
            'quantity' => 6,
        ])->assertSessionHasErrors('quantity');
        $item->forceFill(['current_stock' => PHP_INT_MAX])->save();
        $this->post(route('admin.inventory-items.movements.store', $item), [
            'type' => 'import',
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(PHP_INT_MAX, $item->fresh()->current_stock);
    }

    public function test_persistence_failure_rolls_back_movement_and_stock(): void
    {
        $manager = $this->user('manager');
        $item = $this->item('ROLLBACK', 10);
        Event::listen(
            'eloquent.updating: '.InventoryItem::class,
            fn () => throw new RuntimeException('stock update failed'),
        );
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($manager)->post(route('admin.inventory-items.movements.store', $item), [
                'type' => 'import',
                'quantity' => 5,
            ]);
            $this->fail('Expected stock update failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('stock update failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(10, $item->fresh()->current_stock);
    }

    public function test_movement_history_is_immutable(): void
    {
        $manager = $this->user('manager');
        $item = $this->item('IMMUTABLE', 10);
        $this->actingAs($manager)->post(route('admin.inventory-items.movements.store', $item), [
            'type' => 'export',
            'quantity' => 2,
        ]);
        $movement = StockMovement::query()->sole();
        try {
            $movement->forceFill(['quantity' => 1])->save();
            $this->fail('Movement update must fail.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }
        try {
            $movement->delete();
            $this->fail('Movement delete must fail.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }
        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'quantity' => 2,
            'stock_before' => 10,
            'stock_after' => 8,
        ]);
    }

    public function test_low_stock_boundary_search_and_filters_work(): void
    {
        $manager = $this->user('manager');
        $product = $this->product('Special Linked Product');
        $lowZero = $this->item('LOW-ZERO', 0, 0, product: $product);
        $this->item('LOW-EQUAL', 5, 5);
        $this->item('ENOUGH', 6, 5);
        $this->item('INACTIVE', 1, 5, InventoryItem::STATUS_INACTIVE);
        $this->actingAs($manager)
            ->get(route('admin.inventory-items.index', ['low_stock' => 1]))
            ->assertOk()
            ->assertSee('LOW-ZERO')
            ->assertSee('LOW-EQUAL')
            ->assertSee('INACTIVE')
            ->assertDontSee('ENOUGH');
        $this->get(route('admin.inventory-items.index', ['q' => 'Special Linked']))
            ->assertOk()
            ->assertSee($lowZero->sku)
            ->assertDontSee('ENOUGH');
        $this->get(route('admin.inventory-items.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('INACTIVE')
            ->assertDontSee('LOW-EQUAL');
        $this->get(route('admin.inventory-items.show', $lowZero))
            ->assertOk()
            ->assertSee(__('inventory.low_stock_warning'));
    }

    public function test_deleted_or_inactive_item_rejects_new_movement(): void
    {
        $manager = $this->user('manager');
        $inactive = $this->item('INACTIVE-MOVE', 10, 0, InventoryItem::STATUS_INACTIVE);
        $this->actingAs($manager)
            ->post(route('admin.inventory-items.movements.store', $inactive), ['type' => 'export', 'quantity' => 1])
            ->assertSessionHasErrors('inventory_item');
        $deleted = $this->item('DELETED-MOVE', 10);
        $deleted->delete();
        $this->post(route('admin.inventory-items.movements.store', $deleted), [
            'type' => 'export',
            'quantity' => 1,
        ])->assertNotFound();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_authorization_view_and_movement_permissions_are_independent(): void
    {
        $item = $this->item('AUTH', 10);
        $this->get(route('admin.inventory-items.index'))->assertRedirect(route('login'));
        foreach (['staff', 'kitchen', 'customer'] as $role) {
            $this->actingAs($this->user($role, $role !== 'customer'))
                ->get(route('admin.inventory-items.index'))
                ->assertForbidden();
        }
        $manager = $this->user('manager');
        $this->actingAs($manager)->get(route('admin.inventory-items.index'))->assertOk();
        $permission = Permission::where('code', 'inventory.stock-movement.create')->firstOrFail();
        $manager->role->permissions()->detach($permission);
        $this->post(route('admin.inventory-items.movements.store', $item), [
            'type' => 'import',
            'quantity' => 1,
        ])->assertForbidden();
        $this->get(route('admin.inventory-items.show', $item))->assertOk();
        $manager->role->permissions()->attach($permission);
        $view = Permission::where('code', 'inventory.view')->firstOrFail();
        $manager->role->permissions()->detach($view);
        $this->get(route('admin.inventory-items.index'))->assertForbidden();
    }

    public function test_disabled_user_and_employee_are_rejected(): void
    {
        $employeeDisabled = $this->user('manager');
        $employeeDisabled->employee->forceFill(['status' => EmployeeStatus::Disabled])->save();
        $this->actingAs($employeeDisabled)->get(route('admin.inventory-items.index'))->assertForbidden();
        $userDisabled = $this->user('admin');
        $userDisabled->forceFill(['status' => User::STATUS_DISABLED])->save();
        $this->actingAs($userDisabled)->get(route('admin.inventory-items.index'))->assertRedirect(route('login'));
    }

    /** @return array<string, mixed> */
    private function itemPayload(string $sku, ?int $productId, int $minimum = 0, string $status = 'active'): array
    {
        return [
            'sku' => $sku,
            'name' => 'Inventory '.$sku,
            'unit' => 'unit',
            'minimum_stock' => $minimum,
            'status' => $status,
            'product_id' => $productId,
        ];
    }

    private function item(
        string $sku,
        int $stock,
        int $minimum = 0,
        string $status = 'active',
        ?Product $product = null,
    ): InventoryItem {
        return InventoryItem::query()->forceCreate([
            'product_id' => $product?->id,
            'sku' => $sku,
            'name' => $sku,
            'unit' => 'unit',
            'current_stock' => $stock,
            'minimum_stock' => $minimum,
            'status' => $status,
        ]);
    }

    private function product(string $name, string $status = Product::STATUS_ACTIVE): Product
    {
        $category =
            Category::query()->first() ??
            Category::query()->forceCreate([
                'name' => 'Category',
                'slug' => 'inventory-category',
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 1,
            ]);

        return Product::query()->forceCreate([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'inventory-'.fake()->unique()->numberBetween(1, 999999),
            'price' => 10000,
            'status' => $status,
            'is_available' => true,
        ]);
    }

    private function user(string $role, bool $employee = true): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', $role)->firstOrFail())
            ->create();
        if ($employee) {
            Employee::query()->forceCreate([
                'user_id' => $user->id,
                'employee_code' => 'INV-'.fake()->unique()->numberBetween(1, 999999),
                'name' => 'Inventory actor',
                'status' => EmployeeStatus::Active,
            ]);
        }

        return $user;
    }
}
