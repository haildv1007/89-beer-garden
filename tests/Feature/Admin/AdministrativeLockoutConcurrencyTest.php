<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AdministrativeLockoutConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_self_disable_attempts_cannot_remove_every_active_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $first = $this->admin('CONCURRENT-1');
        $second = $this->admin('CONCURRENT-2');
        $startAt = microtime(true) + 1.5;

        $firstProcess = $this->disableProcess($first, $startAt);
        $secondProcess = $this->disableProcess($second, $startAt);
        $firstProcess->start();
        $secondProcess->start();
        $firstProcess->wait();
        $secondProcess->wait();

        $exitCodes = [$firstProcess->getExitCode(), $secondProcess->getExitCode()];
        sort($exitCodes);
        $this->assertSame([0, 2], $exitCodes, $firstProcess->getErrorOutput().$secondProcess->getErrorOutput());

        $activeAdmins = User::query()
            ->join('employees', 'employees.user_id', '=', 'users.id')
            ->where('users.role_id', Role::where('code', 'admin')->value('id'))
            ->where('users.status', User::STATUS_ACTIVE)
            ->where('employees.status', EmployeeStatus::Active->value)
            ->whereNull('employees.deleted_at')
            ->count();
        $this->assertSame(1, $activeAdmins);
    }

    private function admin(string $code): User
    {
        $user = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $user->id, 'employee_code' => $code,
            'name' => $code, 'status' => EmployeeStatus::Active,
        ]);

        return $user;
    }

    private function disableProcess(User $admin, float $startAt): Process
    {
        $script = <<<'PHP'
            chdir($argv[3]);
            require $argv[3].'/vendor/autoload.php';
            $app = require $argv[3].'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            while (microtime(true) < (float) $argv[4]) { usleep(1000); }
            try {
                app(App\Services\Employee\DisableEmployeeService::class)->disable(
                    App\Models\Employee::findOrFail((int) $argv[1]),
                    App\Models\User::findOrFail((int) $argv[2]),
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
        $process = new Process([
            PHP_BINARY, '-r', $script, (string) $admin->employee->id,
            (string) $admin->id, base_path(), (string) $startAt,
        ], base_path(), [
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
        ]);
        $process->setTimeout(20);

        return $process;
    }
}
