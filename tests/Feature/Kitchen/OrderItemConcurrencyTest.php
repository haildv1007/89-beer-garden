<?php

namespace Tests\Feature\Kitchen;

use App\Enums\DiningSessionStatus;
use App\Enums\EmployeeStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class OrderItemConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_kitchen_workers_cannot_start_the_same_item(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$item, $first, $second] = $this->scenario(OrderItemStatus::Waiting);
        $this->race($item, $first, $second, 'start', 'start');
        $this->assertSame(OrderItemStatus::Preparing, $item->fresh()->status);
    }

    public function test_concurrent_start_and_cancel_allows_only_one_transition(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$item, $first, $second] = $this->scenario(OrderItemStatus::Waiting);
        $this->race($item, $first, $second, 'start', 'cancel-waiting');
        $item->refresh();
        $this->assertContains($item->status, [OrderItemStatus::Preparing, OrderItemStatus::Cancelled]);
        if ($item->status === OrderItemStatus::Preparing) {
            $this->assertNull($item->cancelled_by_employee_id);
        }
    }

    public function test_concurrent_ready_and_cancel_allows_only_one_transition(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$item, $first, $second] = $this->scenario(OrderItemStatus::Preparing);
        $this->race($item, $first, $second, 'ready', 'cancel-preparing');
        $item->refresh();
        $this->assertContains($item->status, [OrderItemStatus::Ready, OrderItemStatus::Cancelled]);
        if ($item->status === OrderItemStatus::Ready) {
            $this->assertNull($item->cancelled_by_employee_id);
        }
    }

    /** @return array{OrderItem, User, User} */
    private function scenario(OrderItemStatus $status): array
    {
        $first = $this->actor('CONCURRENT-1');
        $second = $this->actor('CONCURRENT-2');
        $opener = Employee::query()->forceCreate(['employee_code' => 'OPENER', 'name' => 'Opener', 'status' => EmployeeStatus::Active]);
        $table = RestaurantTable::query()->forceCreate(['code' => 'CONCURRENT-TABLE', 'name' => 'Table', 'capacity' => 6, 'runtime_status' => RestaurantTableStatus::Occupied, 'is_active' => true]);
        $session = DiningSession::query()->forceCreate(['session_code' => 'DS-CONCURRENT', 'table_id' => $table->id, 'opened_by_employee_id' => $opener->id, 'status' => DiningSessionStatus::Active, 'started_at' => now(), 'guest_count' => 4]);
        $category = Category::query()->forceCreate(['name' => 'Category', 'slug' => 'concurrent', 'status' => Category::STATUS_ACTIVE, 'sort_order' => 1]);
        $product = Product::query()->forceCreate(['category_id' => $category->id, 'name' => 'Product', 'slug' => 'concurrent-product', 'price' => 10000, 'status' => Product::STATUS_ACTIVE, 'is_available' => true]);
        $order = Order::query()->forceCreate(['order_code' => 'ORD-CONCURRENT', 'dining_session_id' => $session->id, 'source' => 'staff', 'ordered_at' => now()]);
        $item = OrderItem::query()->forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Snapshot', 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000, 'status' => $status]);

        return [$item, $first, $second];
    }

    private function actor(string $code): User
    {
        $user = User::factory()->forRole(Role::where('code', 'manager')->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => $code, 'name' => $code, 'status' => EmployeeStatus::Active]);

        return $user;
    }

    private function race(OrderItem $item, User $first, User $second, string $firstAction, string $secondAction): void
    {
        $startAt = microtime(true) + 1.5;
        $firstProcess = $this->process($item, $first, $firstAction, $startAt);
        $secondProcess = $this->process($item, $second, $secondAction, $startAt);
        $firstProcess->start();
        $secondProcess->start();
        $firstProcess->wait();
        $secondProcess->wait();
        $exitCodes = [$firstProcess->getExitCode(), $secondProcess->getExitCode()];
        sort($exitCodes);
        $this->assertSame([0, 2], $exitCodes, $firstProcess->getErrorOutput().$secondProcess->getErrorOutput());
    }

    private function process(OrderItem $item, User $actor, string $action, float $startAt): Process
    {
        $script = <<<'PHP'
            chdir($argv[4]);
            require $argv[4].'/vendor/autoload.php';
            $app = require $argv[4].'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            while (microtime(true) < (float) $argv[5]) { usleep(1000); }
            try {
                $item = App\Models\OrderItem::findOrFail((int) $argv[1]);
                $actor = App\Models\User::findOrFail((int) $argv[2]);
                match ($argv[3]) {
                    'start' => app(App\Services\OrderItem\OrderItemTransitionService::class)->startPreparing($item, $actor),
                    'ready' => app(App\Services\OrderItem\OrderItemTransitionService::class)->markReady($item, $actor),
                    'cancel-waiting' => app(App\Services\OrderItem\CancelOrderItemService::class)->cancelWaiting($item, $actor, 'Concurrent'),
                    'cancel-preparing' => app(App\Services\OrderItem\CancelOrderItemService::class)->cancelPreparing($item, $actor, 'Concurrent'),
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
        $process = new Process([PHP_BINARY, '-r', $script, (string) $item->id, (string) $actor->id, $action, base_path(), (string) $startAt], base_path(), [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'], 'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'], 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
        ]);
        $process->setTimeout(20);

        return $process;
    }
}
