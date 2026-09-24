<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class StockMovementConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_concurrent_exports_allow_only_one_when_stock_is_insufficient_for_both(): void
    {
        $item = $this->item('RACE-EXPORT', 10);
        $actor = $this->actor();
        $this->assertSame(
            [0, 2],
            $this->race([$this->process($item, $actor, 'export', 7), $this->process($item, $actor, 'export', 7)]),
        );
        $this->assertSame(3, $item->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_concurrent_import_and_export_do_not_lose_updates(): void
    {
        $item = $this->item('RACE-MIXED', 10);
        $actor = $this->actor();
        $this->assertSame(
            [0, 0],
            $this->race([$this->process($item, $actor, 'import', 5), $this->process($item, $actor, 'export', 8)]),
        );
        $this->assertSame(7, $item->fresh()->current_stock);
        $this->assertAuditChain($item, 10, 7, 2);
    }

    public function test_two_concurrent_adjustments_form_one_consistent_audit_chain(): void
    {
        $item = $this->item('RACE-ADJUST', 10);
        $actor = $this->actor();
        $this->assertSame(
            [0, 0],
            $this->race([
                $this->process($item, $actor, 'adjustment_in', 3),
                $this->process($item, $actor, 'adjustment_out', 4),
            ]),
        );
        $this->assertSame(9, $item->fresh()->current_stock);
        $this->assertAuditChain($item, 10, 9, 2);
    }

    public function test_double_submit_records_exactly_one_movement_per_successful_request(): void
    {
        $item = $this->item('RACE-DOUBLE', 10);
        $actor = $this->actor();
        $this->assertSame(
            [0, 0],
            $this->race([$this->process($item, $actor, 'export', 2), $this->process($item, $actor, 'export', 2)]),
        );
        $this->assertSame(6, $item->fresh()->current_stock);
        $this->assertAuditChain($item, 10, 6, 2);
    }

    private function assertAuditChain(InventoryItem $item, int $initial, int $final, int $count): void
    {
        $movements = StockMovement::query()->where('inventory_item_id', $item->id)->orderBy('id')->get();
        $this->assertCount($count, $movements);
        $this->assertSame($initial, $movements->first()->stock_before);
        $this->assertSame($final, $movements->last()->stock_after);
        for ($index = 1; $index < $movements->count(); $index++) {
            $this->assertSame($movements[$index - 1]->stock_after, $movements[$index]->stock_before);
        }
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
            $this->assertNotSame(3, $process->getExitCode(), $process->getErrorOutput());
        }
        $codes = array_map(fn (Process $process) => $process->getExitCode(), $processes);
        sort($codes);

        return $codes;
    }

    private function process(InventoryItem $item, User $actor, string $type, int $quantity): Process
    {
        $script = <<<'PHP'
        chdir($argv[5]);
        require $argv[5].'/vendor/autoload.php';
        $app = require $argv[5].'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $startAt = (float) trim(stream_get_contents(STDIN));
        while (microtime(true) < $startAt) { usleep(1000); }
        try {
            app(App\Services\Inventory\AdjustStockService::class)->record(
                App\Models\InventoryItem::findOrFail((int) $argv[1]),
                App\Models\User::findOrFail((int) $argv[2]),
                App\Enums\StockMovementType::from($argv[3]),
                (int) $argv[4],
                'Concurrent'
            );
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
            [
                PHP_BINARY,
                '-r',
                $script,
                (string) $item->id,
                (string) $actor->id,
                $type,
                (string) $quantity,
                base_path(),
            ],
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

    private function item(string $sku, int $stock): InventoryItem
    {
        return InventoryItem::query()->forceCreate([
            'sku' => $sku,
            'name' => $sku,
            'unit' => 'unit',
            'current_stock' => $stock,
            'minimum_stock' => 0,
            'status' => InventoryItem::STATUS_ACTIVE,
        ]);
    }

    private function actor(): User
    {
        $user = User::factory()
            ->forRole(Role::where('code', 'manager')->firstOrFail())
            ->create();
        Employee::query()->forceCreate([
            'user_id' => $user->id,
            'employee_code' => 'INV-RACE',
            'name' => 'Inventory racer',
            'status' => EmployeeStatus::Active,
        ]);

        return $user;
    }
}
