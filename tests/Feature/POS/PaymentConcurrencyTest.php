<?php

namespace Tests\Feature\POS;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Billing\ApplyVoucherService;
use App\Services\Billing\OpenBillService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PaymentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        SystemSetting::query()->forceCreate([
            'key' => 'customer_ordering_enabled',
            'value' => 'true',
            'type' => 'boolean',
        ]);
    }

    public function test_customer_voucher_apply_or_remove_racing_payment_has_one_consistent_outcome(): void
    {
        foreach (['applyCustomer', 'removeCustomer'] as $action) {
            [$session, $product] = $this->scenario('CUSTOMER-'.strtoupper($action));
            $this->item($session, $product);
            $bill = app(OpenBillService::class)->open($session);
            $voucher = Voucher::query()->forceCreate([
                'code' => 'CV-'.strtoupper($action),
                'name' => 'Customer race',
                'discount_type' => Voucher::TYPE_FIXED,
                'discount_value' => 1000,
                'min_order_amount' => 0,
                'start_at' => now()->subHour(),
                'end_at' => now()->addHour(),
                'used_count' => 0,
                'status' => Voucher::STATUS_ACTIVE,
            ]);
            if ($action === 'removeCustomer') {
                app(ApplyVoucherService::class)->apply($bill, $voucher->code);
            }
            $results = $this->race([
                $this->process('pay', $bill->id, $this->actor('CUSTOMER-PAY-'.$action)->id),
                $this->process($action, $bill->id, $voucher->id, $session->id),
            ]);
            $this->assertContains($results, [[0, 0], [0, 2]]);
            $this->assertLessThanOrEqual(
                1,
                Payment::query()->where('bill_id', $bill->id)->where('status', 'success')->count(),
            );
            $this->assertLessThanOrEqual(1, $voucher->fresh()->used_count);
        }
    }

    public function test_customer_voucher_apply_racing_additional_order_never_keeps_stale_subtotal(): void
    {
        [$session, $product] = $this->scenario('CUSTOMER-ORDER');
        $this->item($session, $product);
        $bill = app(OpenBillService::class)->open($session);
        $voucher = Voucher::query()->forceCreate([
            'code' => 'CUSTOMER-ORDER',
            'name' => 'Race',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 1000,
            'min_order_amount' => 0,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        $results = $this->race([
            $this->process('applyCustomer', $bill->id, $voucher->id, $session->id),
            $this->process('order', $session->id, $this->actor('CUSTOMER-ORDER')->id, $product->id),
        ]);
        $this->assertSame([0, 0], $results);
        $expected = (int) OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('dining_session_id', $session->id))
            ->sum('line_total');
        $this->assertSame($expected, $bill->fresh()->subtotal);
    }

    public function test_customer_voucher_apply_racing_cancellation_never_keeps_stale_totals(): void
    {
        [$session, $product] = $this->scenario('CUSTOMER-CANCEL');
        $this->item($session, $product, OrderItemStatus::Served);
        $cancelled = $this->item($session, $product);
        $bill = app(OpenBillService::class)->open($session);
        $voucher = Voucher::query()->forceCreate([
            'code' => 'CUSTOMER-CANCEL',
            'name' => 'Race',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 1000,
            'min_order_amount' => 0,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        $actor = $this->actor('CUSTOMER-CANCEL');
        $results = $this->race([
            $this->process('applyCustomer', $bill->id, $voucher->id, $session->id),
            $this->process('cancel', $cancelled->id, $actor->id),
        ]);
        $this->assertSame([0, 0], $results);
        $expected = (int) OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('dining_session_id', $session->id))
            ->where('status', '!=', OrderItemStatus::Cancelled->value)
            ->sum('line_total');
        $this->assertSame($expected, $bill->fresh()->subtotal);
        $this->assertSame($expected - 1000, $bill->fresh()->total_amount);
    }

    public function test_two_customer_vouchers_racing_leave_no_mixed_bill_state(): void
    {
        [$session, $product] = $this->scenario('CUSTOMER-TWO');
        $this->item($session, $product);
        $bill = app(OpenBillService::class)->open($session);
        $first = Voucher::query()->forceCreate([
            'code' => 'RACE-A',
            'name' => 'A',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 1000,
            'min_order_amount' => 0,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        $second = Voucher::query()->forceCreate([
            'code' => 'RACE-B',
            'name' => 'B',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 2000,
            'min_order_amount' => 0,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        $results = $this->race([
            $this->process('applyCustomer', $bill->id, $first->id, $session->id),
            $this->process('applyCustomer', $bill->id, $second->id, $session->id),
        ]);
        $this->assertContains($results, [[0, 0], [0, 2]]);
        $fresh = $bill->fresh();
        $expectedDiscount = $fresh->voucher_id === $first->id ? 1000 : 2000;
        $this->assertSame(
            [$expectedDiscount, 10000 - $expectedDiscount],
            [$fresh->discount_amount, $fresh->total_amount],
        );
    }

    public function test_concurrent_payment_creates_only_one_success(): void
    {
        [$session, $product] = $this->scenario();
        $this->item($session, $product, OrderItemStatus::Served, 2);
        $bill = app(OpenBillService::class)->open($session);
        $results = $this->race([
            $this->process('pay', $bill->id, $this->actor('PAY-A')->id),
            $this->process('pay', $bill->id, $this->actor('PAY-B')->id),
        ]);
        $this->assertSame([0, 2], $results);
        $this->assertSame(1, Payment::query()->where('status', PaymentStatus::Success->value)->count());
        $this->assertSame(DiningSessionStatus::Completed, $session->fresh()->status);
    }

    public function test_success_racing_failed_attempt_keeps_exactly_one_success(): void
    {
        [$session, $product] = $this->scenario('FAIL-RACE');
        $this->item($session, $product);
        $bill = app(OpenBillService::class)->open($session);
        $actor = $this->actor('FAIL-RACE');
        $results = $this->race([
            $this->process('pay', $bill->id, $actor->id),
            $this->process('fail', $bill->id, $actor->id),
        ]);
        $this->assertContains($results, [[0, 0], [0, 2]]);
        $this->assertSame(1, Payment::query()->where('status', PaymentStatus::Success->value)->count());
        $this->assertLessThanOrEqual(1, Payment::query()->where('status', PaymentStatus::Failed->value)->count());
    }

    public function test_concurrent_last_voucher_usage_allows_only_one_bill_to_complete(): void
    {
        [$firstSession, $product] = $this->scenario('A');
        [$secondSession] = $this->scenario('B', $product);
        $this->item($firstSession, $product);
        $this->item($secondSession, $product);
        $firstBill = app(OpenBillService::class)->open($firstSession);
        $secondBill = app(OpenBillService::class)->open($secondSession);
        $voucher = Voucher::query()->forceCreate([
            'code' => 'LAST',
            'name' => 'Last use',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 1000,
            'min_order_amount' => 0,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'usage_limit' => 1,
            'used_count' => 0,
            'status' => Voucher::STATUS_ACTIVE,
        ]);
        app(ApplyVoucherService::class)->apply($firstBill, $voucher->code);
        app(ApplyVoucherService::class)->apply($secondBill, $voucher->code);
        $results = $this->race([
            $this->process('pay', $firstBill->id, $this->actor('VOUCHER-A')->id),
            $this->process('pay', $secondBill->id, $this->actor('VOUCHER-B')->id),
        ]);
        $this->assertSame([0, 2], $results);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertSame(1, Payment::query()->where('status', PaymentStatus::Success->value)->count());
    }

    public function test_payment_racing_additional_order_never_omits_committed_items(): void
    {
        [$session, $product] = $this->scenario();
        $this->item($session, $product);
        $bill = app(OpenBillService::class)->open($session);
        $payer = $this->actor('ORDER-PAY');
        $results = $this->race([
            $this->process('pay', $bill->id, $payer->id),
            $this->process('order', $session->id, $payer->id, $product->id),
        ]);
        $this->assertContains($results, [[0, 0], [0, 2]]);
        $expected = (int) OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('dining_session_id', $session->id))
            ->where('status', '!=', OrderItemStatus::Cancelled->value)
            ->sum('line_total');
        $this->assertSame($expected, $bill->fresh()->subtotal);
        $this->assertSame($expected, Payment::query()->where('status', 'success')->sole()->amount);
    }

    public function test_payment_racing_item_cancellation_or_transition_has_no_deadlock_or_drift(): void
    {
        foreach (['cancel', 'prepare'] as $action) {
            [$session, $product] = $this->scenario(strtoupper($action));
            $this->item($session, $product, OrderItemStatus::Served);
            $racingItem = $this->item($session, $product);
            $bill = app(OpenBillService::class)->open($session);
            $actor = $this->actor('ITEM-'.strtoupper($action));
            $results = $this->race([
                $this->process('pay', $bill->id, $actor->id),
                $this->process($action, $racingItem->id, $actor->id),
            ]);
            $this->assertContains($results, [[0, 0], [0, 2]]);
            $expected = (int) OrderItem::query()
                ->whereHas('order', fn ($query) => $query->where('dining_session_id', $session->id))
                ->where('status', '!=', OrderItemStatus::Cancelled->value)
                ->sum('line_total');
            $this->assertSame($expected, $bill->fresh()->subtotal);
            $this->assertSame(
                $expected,
                Payment::query()->where('bill_id', $bill->id)->where('status', 'success')->sole()->amount,
            );
        }
    }

    /** @return array{DiningSession, Product} */
    private function scenario(string $suffix = 'MAIN', ?Product $product = null): array
    {
        $opener = Employee::query()->forceCreate([
            'employee_code' => 'OPEN-'.$suffix,
            'name' => 'Opener',
            'status' => EmployeeStatus::Active,
        ]);
        $table = RestaurantTable::query()->forceCreate([
            'code' => 'T-'.$suffix,
            'name' => 'Table',
            'capacity' => 4,
            'runtime_status' => RestaurantTableStatus::Occupied,
            'is_active' => true,
        ]);
        $session = DiningSession::query()->forceCreate([
            'session_code' => 'DS-'.$suffix,
            'table_id' => $table->id,
            'opened_by_employee_id' => $opener->id,
            'status' => DiningSessionStatus::Active,
            'started_at' => now(),
            'guest_count' => 2,
        ]);
        if ($product === null) {
            $category = Category::query()->forceCreate([
                'name' => 'Category',
                'slug' => 'category-'.$suffix,
                'status' => Category::STATUS_ACTIVE,
                'sort_order' => 1,
            ]);
            $product = Product::query()->forceCreate([
                'category_id' => $category->id,
                'name' => 'Product',
                'slug' => 'product-'.$suffix,
                'price' => 10000,
                'status' => Product::STATUS_ACTIVE,
                'is_available' => true,
            ]);
        }

        return [$session, $product];
    }

    private function item(
        DiningSession $session,
        Product $product,
        OrderItemStatus $status = OrderItemStatus::Waiting,
        int $quantity = 1,
    ): OrderItem {
        $order = Order::query()->forceCreate([
            'order_code' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'dining_session_id' => $session->id,
            'source' => 'staff',
            'ordered_at' => now(),
        ]);

        return OrderItem::query()->forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'line_total' => $product->price * $quantity,
            'status' => $status,
        ]);
    }

    private function actor(string $code): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', 'manager')->firstOrFail())
            ->create();
        Employee::query()->forceCreate([
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $code,
            'status' => EmployeeStatus::Active,
        ]);

        return $user;
    }

    /** @param list<Process> $processes @return list<int|null> */
    private function race(array $processes): array
    {
        $startAt = microtime(true) + 1.5;
        foreach ($processes as $process) {
            $process->setInput((string) $startAt);
            $process->start();
        }
        foreach ($processes as $process) {
            $process->wait();
        }
        $codes = array_map(fn (Process $process) => $process->getExitCode(), $processes);
        sort($codes);
        foreach ($processes as $process) {
            $this->assertNotSame(3, $process->getExitCode(), $process->getErrorOutput());
        }

        return $codes;
    }

    private function process(string $action, int $targetId, int $actorId, int $extraId = 0): Process
    {
        $script = <<<'PHP'
        chdir($argv[5]);
        require $argv[5].'/vendor/autoload.php';
        $app = require $argv[5].'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $startAt = (float) trim(stream_get_contents(STDIN));
        while (microtime(true) < $startAt) {
            usleep(1000);
        }
        try {
            $actor = in_array($argv[1], ['applyCustomer', 'removeCustomer'], true)
                ? null
                : App\Models\User::findOrFail((int) $argv[3]);
            match ($argv[1]) {
                'pay' => app(App\Services\Payment\CompletePaymentService::class)->complete(
                    App\Models\Bill::findOrFail((int) $argv[2]), $actor, 'cash', null
                ),
                'fail' => app(App\Services\Payment\RecordFailedPaymentService::class)->record(
                    App\Models\Bill::findOrFail((int) $argv[2]), $actor, 'cash', null, 'Concurrent failure'
                ),
                'order' => app(App\Services\Order\CreateOrderService::class)->create(
                    App\Models\DiningSession::findOrFail((int) $argv[2]),
                    $actor,
                    [['product_id' => (int) $argv[4], 'quantity' => 1]],
                    null
                ),
                'cancel' => app(App\Services\OrderItem\CancelOrderItemService::class)->cancelWaiting(
                    App\Models\OrderItem::findOrFail((int) $argv[2]), $actor, 'Concurrent'
                ),
                'prepare' => app(App\Services\OrderItem\OrderItemTransitionService::class)->startPreparing(
                    App\Models\OrderItem::findOrFail((int) $argv[2]), $actor
                ),
                'applyCustomer' => app(App\Services\Billing\ApplyVoucherService::class)->applyForCustomer(
                    App\Models\Bill::findOrFail((int) $argv[2]),
                    App\Models\Voucher::findOrFail((int) $argv[3])->code,
                    (int) $argv[4]
                ),
                'removeCustomer' => app(App\Services\Billing\ApplyVoucherService::class)->removeForCustomer(
                    App\Models\Bill::findOrFail((int) $argv[2]), (int) $argv[4]
                ),
            };
            exit(0);
        } catch (Illuminate\Validation\ValidationException) {
            exit(2);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.': '.$exception->getMessage());
            exit(3);
        }
        PHP;
        $connection = config('database.connections.mysql');
        $process = new Process(
            [PHP_BINARY, '-r', $script, $action, (string) $targetId, (string) $actorId, (string) $extraId, base_path()],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'APP_KEY' => (string) config('app.key'),
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => (string) $connection['host'],
                'DB_PORT' => (string) $connection['port'],
                'DB_DATABASE' => (string) $connection['database'],
                'DB_USERNAME' => (string) $connection['username'],
                'DB_PASSWORD' => (string) $connection['password'],
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
            ],
        );
        $process->setTimeout(20);

        return $process;
    }
}
