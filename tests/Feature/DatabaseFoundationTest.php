<?php

namespace Tests\Feature;

use App\Enums\BillStatus;
use App\Enums\DiningSessionStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RestaurantTableStatus;
use App\Enums\StockMovementType;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Models\Translation;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_schema_and_important_column_metadata_match_the_baseline(): void
    {
        $coreTables = [
            'roles', 'permissions', 'role_permissions', 'users', 'employees', 'categories', 'products',
            'customers', 'restaurant_tables', 'reservations', 'dining_sessions', 'orders', 'order_items',
            'vouchers', 'bills', 'payments', 'inventory_items', 'stock_movements', 'translations', 'system_settings',
        ];

        foreach ($coreTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing core table: {$table}");
        }

        $excludedTables = [
            'product_images', 'reservation_tables', 'user_roles', 'bill_vouchers', 'voucher_usages',
            'bill_items', 'recipes', 'ingredients', 'recipe_items', 'recommendations', 'recommendation_logs',
            'translation_cache', 'reports', 'report_snapshots', 'audit_logs', 'suppliers', 'purchases',
            'purchase_items', 'invoices',
        ];

        foreach ($excludedTables as $table) {
            $this->assertFalse(Schema::hasTable($table), "Excluded table exists: {$table}");
        }

        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->assertColumn('products', 'price', 'bigint', false, null, 'bigint unsigned');
        $this->assertColumn('order_items', 'unit_price', 'bigint', false, null, 'bigint unsigned');
        $this->assertColumn('payments', 'amount', 'bigint', false, null, 'bigint unsigned');
        $this->assertColumn('employees', 'user_id', 'bigint', true, null, 'bigint unsigned');
        $this->assertColumn('dining_sessions', 'reservation_id', 'bigint', true, null, 'bigint unsigned');
        $this->assertColumn('translations', 'locale', 'varchar', false, null, 'varchar(5)');

        foreach ([
            ['categories', 'sort_order'], ['products', 'is_available'], ['restaurant_tables', 'is_active'],
            ['vouchers', 'min_order_amount'], ['vouchers', 'used_count'], ['bills', 'discount_amount'],
        ] as [$table, $column]) {
            $this->assertNull($this->column($table, $column)->default_value, "{$table}.{$column} has an unapproved default");
        }
    }

    public function test_foreign_keys_delete_rules_unique_constraints_and_composite_indexes_exist(): void
    {
        foreach ([
            ['users', 'role_id', 'roles', 'RESTRICT'],
            ['employees', 'user_id', 'users', 'SET NULL'],
            ['customers', 'user_id', 'users', 'SET NULL'],
            ['order_items', 'product_id', 'products', 'RESTRICT'],
            ['payments', 'processed_by_employee_id', 'employees', 'RESTRICT'],
            ['translations', 'updated_by_employee_id', 'employees', 'RESTRICT'],
            ['system_settings', 'updated_by_employee_id', 'employees', 'RESTRICT'],
        ] as [$table, $column, $referencedTable, $deleteRule]) {
            $foreignKey = $this->foreignKey($table, $column);
            $this->assertNotNull($foreignKey, "Missing FK {$table}.{$column}");
            $this->assertSame($referencedTable, $foreignKey->referenced_table);
            $this->assertSame($deleteRule, $foreignKey->rule);
        }

        foreach ([
            ['employees', 'employees_user_id_unique', ['user_id']],
            ['customers', 'customers_user_id_unique', ['user_id']],
            ['dining_sessions', 'dining_sessions_reservation_id_unique', ['reservation_id']],
            ['bills', 'bills_dining_session_id_unique', ['dining_session_id']],
            ['inventory_items', 'inventory_items_product_id_unique', ['product_id']],
            ['translations', 'translations_entity_field_locale_unique', ['translatable_type', 'translatable_id', 'field', 'locale']],
        ] as [$table, $index, $columns]) {
            $this->assertIndex($table, $index, $columns, true);
        }

        foreach ([
            ['products', 'products_category_id_status_index', ['category_id', 'status']],
            ['reservations', 'reservations_reservation_date_reservation_time_status_index', ['reservation_date', 'reservation_time', 'status']],
            ['reservations', 'reservations_table_schedule_status_index', ['table_id', 'reservation_date', 'reservation_time', 'status']],
            ['restaurant_tables', 'restaurant_tables_runtime_status_is_active_index', ['runtime_status', 'is_active']],
            ['dining_sessions', 'dining_sessions_table_id_status_index', ['table_id', 'status']],
            ['orders', 'orders_dining_session_id_ordered_at_index', ['dining_session_id', 'ordered_at']],
            ['order_items', 'order_items_order_id_status_index', ['order_id', 'status']],
            ['payments', 'payments_bill_id_status_index', ['bill_id', 'status']],
            ['stock_movements', 'stock_movements_inventory_item_id_created_at_index', ['inventory_item_id', 'created_at']],
        ] as [$table, $index, $columns]) {
            $this->assertIndex($table, $index, $columns, false);
        }
    }

    public function test_approved_check_constraint_groups_exist_without_canonical_enum_checks(): void
    {
        $expected = [
            'chk_products_price_non_negative',
            'chk_restaurant_tables_capacity_positive',
            'chk_reservations_party_size_positive',
            'chk_dining_sessions_guest_count_positive',
            'chk_dining_sessions_temporal',
            'chk_orders_source',
            'chk_order_items_quantity_positive',
            'chk_order_items_money_non_negative',
            'chk_vouchers_discount_type',
            'chk_vouchers_amounts_non_negative',
            'chk_vouchers_usage_non_negative',
            'chk_bills_money_non_negative',
            'chk_payments_amount_positive',
            'chk_payments_method',
            'chk_inventory_stock_non_negative',
            'chk_stock_movements_quantity_positive',
            'chk_stock_movements_stock_non_negative',
            'chk_translations_locale',
            'chk_translations_source',
            'chk_system_settings_type',
        ];

        $actual = $this->checkConstraintNames();
        foreach ($expected as $constraint) {
            $this->assertContains($constraint, $actual, "Missing CHECK constraint: {$constraint}");
        }

        foreach ([
            'chk_restaurant_tables_runtime_status', 'chk_reservations_status', 'chk_dining_sessions_status',
            'chk_order_items_status', 'chk_bills_status', 'chk_payments_status', 'chk_stock_movements_type',
            'chk_vouchers_temporal',
        ] as $constraint) {
            $this->assertNotContains($constraint, $actual, "Unapproved CHECK constraint exists: {$constraint}");
        }
    }

    public function test_check_constraint_groups_reject_invalid_writes_while_enum_columns_remain_varchar(): void
    {
        $graph = $this->createOperationalGraph();

        $this->assertQueryRejected(fn () => RestaurantTable::forceCreate(['code' => 'T-BAD', 'name' => 'Bad', 'capacity' => 0, 'runtime_status' => 'available', 'is_active' => true]));
        $this->assertQueryRejected(fn () => Product::forceCreate(['category_id' => $graph['category']->id, 'name' => 'Bad', 'slug' => 'bad-price', 'price' => -1, 'status' => 'active', 'is_available' => true]));
        $this->assertQueryRejected(fn () => DiningSession::forceCreate(['session_code' => 'DS-TIME', 'table_id' => $graph['table']->id, 'opened_by_employee_id' => $graph['employee']->id, 'status' => 'active', 'started_at' => now(), 'ended_at' => now()->subMinute(), 'guest_count' => 1]));
        $this->assertQueryRejected(fn () => Order::forceCreate(['order_code' => 'O-SOURCE', 'dining_session_id' => $graph['session']->id, 'source' => 'delivery', 'ordered_at' => now()]));
        $this->assertQueryRejected(fn () => OrderItem::forceCreate(['order_id' => $graph['order']->id, 'product_id' => $graph['product']->id, 'product_name' => 'Bad', 'quantity' => 0, 'unit_price' => 1, 'line_total' => 0, 'status' => 'waiting']));
        $this->assertQueryRejected(fn () => Bill::forceCreate(['bill_code' => 'B-MONEY', 'dining_session_id' => DiningSession::forceCreate(['session_code' => 'DS-BILL', 'table_id' => $graph['table']->id, 'opened_by_employee_id' => $graph['employee']->id, 'status' => 'active', 'started_at' => now(), 'guest_count' => 1])->id, 'subtotal' => -1, 'discount_amount' => 0, 'total_amount' => 0, 'status' => 'draft']));
        $this->assertQueryRejected(fn () => Payment::forceCreate(['payment_code' => 'P-AMOUNT', 'bill_id' => $graph['bill']->id, 'processed_by_employee_id' => $graph['employee']->id, 'method' => 'cash', 'amount' => 0, 'status' => 'pending']));
        $this->assertQueryRejected(fn () => Payment::forceCreate(['payment_code' => 'P-METHOD', 'bill_id' => $graph['bill']->id, 'processed_by_employee_id' => $graph['employee']->id, 'method' => 'card', 'amount' => 1, 'status' => 'pending']));
        $this->assertQueryRejected(fn () => InventoryItem::forceCreate(['product_id' => null, 'sku' => 'SKU-NEG', 'name' => 'Bad', 'unit' => 'item', 'current_stock' => -1, 'minimum_stock' => 0, 'status' => 'active']));
        $this->assertQueryRejected(fn () => StockMovement::forceCreate(['inventory_item_id' => $graph['inventory']->id, 'type' => 'import', 'quantity' => 0, 'stock_before' => 10, 'stock_after' => 10, 'created_by_employee_id' => $graph['employee']->id]));
        $this->assertQueryRejected(fn () => Translation::forceCreate(['translatable_type' => Product::class, 'translatable_id' => $graph['product']->id, 'field' => 'description', 'locale' => 'vi', 'source_text' => 'VI', 'translated_text' => 'VI', 'source_hash' => hash('sha256', 'VI'), 'source' => 'manual']));
        $this->assertQueryRejected(fn () => SystemSetting::forceCreate(['key' => 'bad_type', 'value' => '{}', 'type' => 'json']));

        DB::table('restaurant_tables')->insert(['code' => 'T-VARCHAR', 'name' => 'VARCHAR', 'capacity' => 1, 'runtime_status' => 'not-a-canonical-state', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        Voucher::forceCreate(['code' => 'V-REVERSED', 'name' => 'Reversed', 'discount_type' => 'fixed', 'discount_value' => 0, 'min_order_amount' => 0, 'start_at' => now(), 'end_at' => now()->subDay(), 'usage_limit' => null, 'used_count' => 0, 'status' => 'active']);

        $this->assertDatabaseHas('restaurant_tables', ['code' => 'T-VARCHAR', 'runtime_status' => 'not-a-canonical-state']);
        $this->assertDatabaseHas('vouchers', ['code' => 'V-REVERSED']);
    }

    public function test_sensitive_action_seed_is_idempotent_and_not_presented_as_a_full_permission_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['admin', 'customer', 'kitchen', 'manager', 'staff'], Role::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame([
            'employee.disable', 'inventory.adjust', 'order-item.cancel', 'payment.complete',
            'permission.assign', 'product.update-price', 'settings.update', 'translation.update',
        ], Permission::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame(['order-item.cancel', 'payment.complete'], $this->permissionCodes('staff'));
        $this->assertSame([], $this->permissionCodes('kitchen'));
        $this->assertSame([], $this->permissionCodes('customer'));
        $this->assertCount(5, $this->permissionCodes('manager'));
        $this->assertCount(8, $this->permissionCodes('admin'));
        $this->assertDatabaseMissing('system_settings', ['key' => 'no_show_timeout_minutes']);
    }

    public function test_all_canonical_enum_casts_and_primary_relationships_work(): void
    {
        $graph = $this->createOperationalGraph();

        $this->assertSame(RestaurantTableStatus::Occupied, $graph['table']->runtime_status);
        $this->assertSame(ReservationStatus::CheckedIn, $graph['reservation']->status);
        $this->assertSame(DiningSessionStatus::Active, $graph['session']->status);
        $this->assertSame(OrderItemStatus::Waiting, $graph['item']->status);
        $this->assertSame(BillStatus::Unpaid, $graph['bill']->status);
        $this->assertSame(PaymentStatus::Pending, $graph['payment']->status);
        $this->assertSame(StockMovementType::Import, $graph['movement']->type);

        $this->assertTrue($graph['role']->permissions->contains($graph['permission']));
        $this->assertTrue($graph['user']->employee->is($graph['employee']));
        $this->assertTrue($graph['user']->customer->is($graph['customer']));
        $this->assertTrue($graph['category']->products->contains($graph['product']));
        $this->assertTrue($graph['customer']->reservations->contains($graph['reservation']));
        $this->assertTrue($graph['table']->diningSessions->contains($graph['session']));
        $this->assertTrue($graph['reservation']->diningSession->is($graph['session']));
        $this->assertTrue($graph['session']->orders->contains($graph['order']));
        $this->assertTrue($graph['order']->items->contains($graph['item']));
        $this->assertTrue($graph['product']->orderItems->contains($graph['item']));
        $this->assertTrue($graph['session']->bill->is($graph['bill']));
        $this->assertTrue($graph['voucher']->bills->contains($graph['bill']));
        $this->assertTrue($graph['bill']->payments->contains($graph['payment']));
        $this->assertTrue($graph['product']->inventoryItem->is($graph['inventory']));
        $this->assertTrue($graph['inventory']->stockMovements->contains($graph['movement']));
        $this->assertTrue($graph['translation']->translatable->is($graph['product']));
        $this->assertTrue($graph['product']->translations->contains($graph['translation']));
        $this->assertTrue($graph['setting']->updatedBy->is($graph['employee']));
    }

    public function test_all_nullable_unique_relationships_accept_multiple_nulls_and_reject_duplicate_values(): void
    {
        $graph = $this->createOperationalGraph();

        Employee::forceCreate(['user_id' => null, 'employee_code' => 'E002', 'name' => 'No Account 1', 'status' => 'active']);
        Employee::forceCreate(['user_id' => null, 'employee_code' => 'E003', 'name' => 'No Account 2', 'status' => 'active']);
        Customer::forceCreate(['user_id' => null, 'name' => 'Guest 1']);
        Customer::forceCreate(['user_id' => null, 'name' => 'Guest 2']);
        InventoryItem::forceCreate(['product_id' => null, 'sku' => 'SKU-NULL-1', 'name' => 'Loose 1', 'unit' => 'item', 'current_stock' => 0, 'minimum_stock' => 0, 'status' => 'active']);
        InventoryItem::forceCreate(['product_id' => null, 'sku' => 'SKU-NULL-2', 'name' => 'Loose 2', 'unit' => 'item', 'current_stock' => 0, 'minimum_stock' => 0, 'status' => 'active']);
        DiningSession::forceCreate(['session_code' => 'DS-NULL-1', 'table_id' => $graph['table']->id, 'reservation_id' => null, 'opened_by_employee_id' => $graph['employee']->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 1]);
        DiningSession::forceCreate(['session_code' => 'DS-NULL-2', 'table_id' => $graph['table']->id, 'reservation_id' => null, 'opened_by_employee_id' => $graph['employee']->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 1]);

        $this->assertQueryRejected(fn () => Employee::forceCreate(['user_id' => $graph['user']->id, 'employee_code' => 'E-DUP', 'name' => 'Duplicate', 'status' => 'active']));
        $this->assertQueryRejected(fn () => Customer::forceCreate(['user_id' => $graph['user']->id, 'name' => 'Duplicate']));
        $this->assertQueryRejected(fn () => DiningSession::forceCreate(['session_code' => 'DS-DUP', 'table_id' => $graph['table']->id, 'reservation_id' => $graph['reservation']->id, 'opened_by_employee_id' => $graph['employee']->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 1]));
        $this->assertQueryRejected(fn () => InventoryItem::forceCreate(['product_id' => $graph['product']->id, 'sku' => 'SKU-DUP', 'name' => 'Duplicate', 'unit' => 'item', 'current_stock' => 0, 'minimum_stock' => 0, 'status' => 'active']));
        $this->assertQueryRejected(fn () => Bill::forceCreate(['bill_code' => 'B-DUP', 'dining_session_id' => $graph['session']->id, 'subtotal' => 0, 'discount_amount' => 0, 'total_amount' => 0, 'status' => BillStatus::Draft]));
    }

    public function test_transaction_history_survives_soft_delete_and_force_delete_is_restricted(): void
    {
        $graph = $this->createOperationalGraph();

        foreach (['product', 'category', 'employee', 'customer', 'table', 'voucher', 'inventory'] as $key) {
            $graph[$key]->delete();
            $this->assertSoftDeleted($graph[$key]->getTable(), ['id' => $graph[$key]->id]);
        }

        $this->assertDatabaseHas('order_items', [
            'id' => $graph['item']->id,
            'product_id' => $graph['product']->id,
            'product_name' => 'Lager',
            'unit_price' => 50000,
            'line_total' => 100000,
        ]);
        $this->assertDatabaseHas('payments', ['id' => $graph['payment']->id, 'processed_by_employee_id' => $graph['employee']->id]);
        $this->assertDatabaseHas('stock_movements', ['id' => $graph['movement']->id, 'inventory_item_id' => $graph['inventory']->id]);

        $this->assertQueryRejected(fn () => $graph['product']->forceDelete());
        $this->assertDatabaseHas('products', ['id' => $graph['product']->id]);
    }

    public function test_factory_make_is_non_mutating_and_create_requires_an_explicit_role(): void
    {
        $this->assertDatabaseCount('roles', 0);

        $made = User::factory()->make();

        $this->assertNull($made->role_id);
        $this->assertDatabaseCount('roles', 0);

        $role = Role::create(['name' => 'Customer', 'code' => 'customer']);
        $created = User::factory()->forRole($role)->create();

        $this->assertTrue($created->role->is($role));
        $this->assertDatabaseCount('roles', 1);
    }

    public function test_server_owned_integrity_fields_are_not_mass_assignable(): void
    {
        $user = new User(['email' => 'safe@example.test', 'password' => 'secret', 'role_id' => 99, 'status' => 'active']);
        $item = new OrderItem(['quantity' => 2, 'status' => 'served', 'unit_price' => 1, 'line_total' => 2, 'cancelled_by_employee_id' => 99]);
        $payment = new Payment(['method' => 'cash', 'amount' => 1, 'status' => 'success', 'processed_by_employee_id' => 99]);
        $movement = new StockMovement(['type' => StockMovementType::Import, 'quantity' => 1, 'stock_before' => 0, 'stock_after' => 1, 'created_by_employee_id' => 99]);
        $session = new DiningSession(['guest_count' => 2, 'table_id' => 99, 'status' => 'completed', 'opened_by_employee_id' => 99]);

        $this->assertNull($user->role_id);
        $this->assertNull($user->status);
        $this->assertSame(2, $item->quantity);
        $this->assertNull($item->status);
        $this->assertNull($item->unit_price);
        $this->assertNull($item->line_total);
        $this->assertNull($item->cancelled_by_employee_id);
        $this->assertSame('cash', $payment->method);
        $this->assertNull($payment->amount);
        $this->assertNull($payment->status);
        $this->assertNull($payment->processed_by_employee_id);
        $this->assertSame(1, $movement->quantity);
        $this->assertNull($movement->stock_before);
        $this->assertNull($movement->stock_after);
        $this->assertNull($movement->created_by_employee_id);
        $this->assertSame(2, $session->guest_count);
        $this->assertNull($session->table_id);
        $this->assertNull($session->status);
        $this->assertNull($session->opened_by_employee_id);
    }

    public function test_full_rollback_and_remigration_work_on_an_isolated_database(): void
    {
        $database = 'phase3_rollback_'.getmypid().'_'.Str::lower(Str::random(6));
        $base = config('database.connections.mysql');
        config([
            'database.connections.phase3_admin' => array_merge($base, ['database' => null]),
            'database.connections.phase3_rollback' => array_merge($base, ['database' => $database]),
        ]);

        DB::purge('phase3_admin');
        DB::connection('phase3_admin')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        try {
            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'phase3_rollback', '--force' => true]));
            $this->assertTrue(Schema::connection('phase3_rollback')->hasTable('payments'));

            $this->assertSame(0, Artisan::call('migrate:rollback', ['--database' => 'phase3_rollback', '--force' => true]));
            $this->assertFalse(Schema::connection('phase3_rollback')->hasTable('users'));

            $this->assertSame(0, Artisan::call('migrate', ['--database' => 'phase3_rollback', '--force' => true]));
            $this->assertTrue(Schema::connection('phase3_rollback')->hasTable('system_settings'));
        } finally {
            DB::disconnect('phase3_rollback');
            DB::purge('phase3_rollback');
            DB::connection('phase3_admin')->statement("DROP DATABASE IF EXISTS `{$database}`");
            DB::disconnect('phase3_admin');
            DB::purge('phase3_admin');
        }
    }

    /** @return array<string, Model> */
    private function createOperationalGraph(): array
    {
        $role = Role::create(['name' => 'Staff', 'code' => 'staff']);
        $permission = Permission::create(['name' => 'Complete payment', 'code' => 'payment.complete']);
        $role->permissions()->attach($permission);
        $user = User::forceCreate(['email' => 'staff@example.test', 'password' => 'secret', 'role_id' => $role->id, 'status' => 'active']);
        $employee = Employee::forceCreate(['user_id' => $user->id, 'employee_code' => 'E001', 'name' => 'Staff', 'status' => 'active']);
        $customer = Customer::forceCreate(['user_id' => $user->id, 'name' => 'Customer']);
        $table = RestaurantTable::forceCreate(['code' => 'T01', 'name' => 'Table 1', 'capacity' => 4, 'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);
        $reservation = Reservation::forceCreate(['customer_id' => $customer->id, 'table_id' => $table->id, 'reservation_code' => 'R001', 'reservation_date' => '2026-08-24', 'reservation_time' => '18:00', 'party_size' => 2, 'status' => ReservationStatus::CheckedIn, 'confirmed_by_employee_id' => $employee->id]);
        $session = DiningSession::forceCreate(['session_code' => 'DS001', 'table_id' => $table->id, 'customer_id' => $customer->id, 'reservation_id' => $reservation->id, 'opened_by_employee_id' => $employee->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 2]);
        $category = Category::forceCreate(['name' => 'Beer', 'slug' => 'beer', 'status' => 'active', 'sort_order' => 1]);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Lager', 'slug' => 'lager', 'price' => 50000, 'status' => 'active', 'is_available' => true]);
        $order = Order::forceCreate(['order_code' => 'O001', 'dining_session_id' => $session->id, 'created_by_employee_id' => $employee->id, 'source' => 'staff', 'ordered_at' => now()]);
        $item = OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Lager', 'quantity' => 2, 'unit_price' => 50000, 'line_total' => 100000, 'status' => OrderItemStatus::Waiting]);
        $voucher = Voucher::forceCreate(['code' => 'V001', 'name' => 'Voucher', 'discount_type' => 'fixed', 'discount_value' => 10000, 'min_order_amount' => 0, 'start_at' => now(), 'end_at' => now()->addDay(), 'usage_limit' => 10, 'used_count' => 0, 'status' => 'active']);
        $bill = Bill::forceCreate(['bill_code' => 'B001', 'dining_session_id' => $session->id, 'voucher_id' => $voucher->id, 'subtotal' => 100000, 'discount_amount' => 10000, 'total_amount' => 90000, 'status' => BillStatus::Unpaid]);
        $payment = Payment::forceCreate(['payment_code' => 'P001', 'bill_id' => $bill->id, 'processed_by_employee_id' => $employee->id, 'method' => 'cash', 'amount' => 90000, 'status' => PaymentStatus::Pending]);
        $inventory = InventoryItem::forceCreate(['product_id' => $product->id, 'sku' => 'SKU001', 'name' => 'Lager stock', 'unit' => 'bottle', 'current_stock' => 10, 'minimum_stock' => 2, 'status' => 'active']);
        $movement = StockMovement::forceCreate(['inventory_item_id' => $inventory->id, 'type' => StockMovementType::Import, 'quantity' => 10, 'stock_before' => 0, 'stock_after' => 10, 'created_by_employee_id' => $employee->id]);
        $translation = Translation::forceCreate(['translatable_type' => Product::class, 'translatable_id' => $product->id, 'field' => 'name', 'locale' => 'en', 'source_text' => 'Bia', 'translated_text' => 'Beer', 'source_hash' => hash('sha256', 'Bia'), 'source' => 'manual', 'updated_by_employee_id' => $employee->id]);
        $setting = SystemSetting::forceCreate(['key' => 'no_show_timeout_minutes', 'value' => '30', 'type' => 'integer', 'updated_by_employee_id' => $employee->id]);

        return compact('role', 'permission', 'user', 'employee', 'customer', 'table', 'reservation', 'session', 'category', 'product', 'order', 'item', 'voucher', 'bill', 'payment', 'inventory', 'movement', 'translation', 'setting');
    }

    private function column(string $table, string $column): object
    {
        return DB::selectOne(
            'SELECT data_type AS type_name, column_type AS full_type, is_nullable AS nullable_value, column_default AS default_value FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column],
        );
    }

    private function assertColumn(string $table, string $column, string $type, bool $nullable, mixed $default, string $columnType): void
    {
        $metadata = $this->column($table, $column);
        $this->assertSame($type, $metadata->type_name);
        $this->assertSame($columnType, $metadata->full_type);
        $this->assertSame($nullable ? 'YES' : 'NO', $metadata->nullable_value);
        $this->assertSame($default, $metadata->default_value);
    }

    private function foreignKey(string $table, string $column): ?object
    {
        return DB::selectOne(
            <<<'SQL'
                SELECT kcu.referenced_table_name AS referenced_table, rc.delete_rule AS rule
                FROM information_schema.key_column_usage kcu
                JOIN information_schema.referential_constraints rc
                  ON rc.constraint_schema = kcu.constraint_schema
                 AND rc.constraint_name = kcu.constraint_name
                 AND rc.table_name = kcu.table_name
                WHERE kcu.table_schema = DATABASE()
                  AND kcu.table_name = ?
                  AND kcu.column_name = ?
                  AND kcu.referenced_table_name IS NOT NULL
                SQL,
            [$table, $column],
        );
    }

    /** @param list<string> $expectedColumns */
    private function assertIndex(string $table, string $index, array $expectedColumns, bool $unique): void
    {
        $rows = DB::select(
            'SELECT column_name AS indexed_column, non_unique AS is_non_unique FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY seq_in_index',
            [$table, $index],
        );

        $this->assertNotEmpty($rows, "Missing index {$table}.{$index}");
        $this->assertSame($expectedColumns, array_column($rows, 'indexed_column'));
        $this->assertSame($unique ? 0 : 1, (int) $rows[0]->is_non_unique);
    }

    /** @return list<string> */
    private function checkConstraintNames(): array
    {
        return array_column(DB::select(
            "SELECT constraint_name AS check_name FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND constraint_type = 'CHECK'",
        ), 'check_name');
    }

    /** @return list<string> */
    private function permissionCodes(string $role): array
    {
        return Role::where('code', $role)->firstOrFail()->permissions()->orderBy('code')->pluck('code')->all();
    }

    private function assertQueryRejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Database accepted invalid integrity data.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
