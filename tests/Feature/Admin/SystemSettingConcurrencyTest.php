<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSetting\SystemSettingCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SystemSettingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_concurrent_create_never_duplicates_key_and_keeps_winning_audit_actor(): void
    {
        $first = $this->admin('SET-RACE-1');
        $second = $this->admin('SET-RACE-2');
        $this->race([$this->process($first, '30'), $this->process($second, '60')]);

        $setting = SystemSetting::query()->where('key', SystemSettingCatalog::NO_SHOW_TIMEOUT)->sole();
        $expectedActor = $setting->value === '30' ? $first->employee->id : $second->employee->id;
        $this->assertContains($setting->value, ['30', '60']);
        $this->assertSame($expectedActor, $setting->updated_by_employee_id);
        $this->assertSame('integer', $setting->type);
    }

    public function test_concurrent_updates_leave_one_complete_canonical_state(): void
    {
        $first = $this->admin('SET-RACE-3');
        $second = $this->admin('SET-RACE-4');
        SystemSetting::query()->forceCreate(['key' => SystemSettingCatalog::NO_SHOW_TIMEOUT,
            'value' => '15', 'type' => 'integer', 'updated_by_employee_id' => $first->employee->id]);
        $this->race([$this->process($first, '90'), $this->process($second, '120')]);

        $setting = SystemSetting::query()->sole();
        $expectedActor = $setting->value === '90' ? $first->employee->id : $second->employee->id;
        $this->assertContains($setting->value, ['90', '120']);
        $this->assertSame($expectedActor, $setting->updated_by_employee_id);
        $this->assertSame('integer', $setting->type);
    }

    /** @param list<Process> $processes */
    private function race(array $processes): void
    {
        $startAt = microtime(true) + 1.5;
        foreach ($processes as $process) {
            $process->setInput((string) $startAt);
            $process->start();
        }
        foreach ($processes as $process) {
            $process->wait();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        }
        $this->assertDatabaseCount('system_settings', 1);
    }

    private function process(User $actor, string $value): Process
    {
        $script = <<<'PHP'
            chdir($argv[4]);
            require $argv[4].'/vendor/autoload.php';
            $app = require $argv[4].'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $startAt = (float) trim(stream_get_contents(STDIN));
            while (microtime(true) < $startAt) { usleep(1000); }
            try {
                app(App\Services\SystemSetting\UpdateSystemSettingService::class)->update(
                    $argv[1], $argv[2], App\Models\User::findOrFail((int) $argv[3])
                );
                exit(0);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getMessage());
                exit(3);
            }
            PHP;
        $connection = config('database.connections.mysql');
        $process = new Process([PHP_BINARY, '-r', $script, SystemSettingCatalog::NO_SHOW_TIMEOUT,
            $value, (string) $actor->id, base_path()], base_path(), [
                'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'), 'DB_CONNECTION' => 'mysql',
                'DB_HOST' => (string) $connection['host'], 'DB_PORT' => (string) $connection['port'],
                'DB_DATABASE' => (string) $connection['database'], 'DB_USERNAME' => (string) $connection['username'],
                'DB_PASSWORD' => (string) $connection['password'], 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
            ]);
        $process->setTimeout(20);

        return $process;
    }

    private function admin(string $code): User
    {
        $user = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate(['user_id' => $user->id, 'employee_code' => $code,
            'name' => $code, 'status' => EmployeeStatus::Active]);

        return $user;
    }
}
