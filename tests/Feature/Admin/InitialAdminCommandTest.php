<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\CreateInitialAdminService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class InitialAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_command_creates_active_admin_and_employee_with_hashed_password(): void
    {
        $password = 'Secure#Pass123';

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion(__('employee.bootstrap_admin.employee_code'), 'ADM-001')
            ->expectsQuestion(__('employee.bootstrap_admin.employee_name'), 'Initial Admin')
            ->expectsQuestion(__('employee.bootstrap_admin.email'), 'ADMIN@EXAMPLE.COM')
            ->expectsQuestion(__('employee.bootstrap_admin.password'), $password)
            ->expectsQuestion(__('employee.bootstrap_admin.password_confirmation'), $password)
            ->expectsOutput(__('employee.bootstrap_admin.created'))
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame('admin', $user->role->code);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotSame($password, $user->password);
        $this->assertSame(EmployeeStatus::Active, $user->employee->status);
        $this->assertSame('ADM-001', $user->employee->employee_code);
    }

    public function test_command_rejects_duplicate_inputs_and_a_second_initial_admin(): void
    {
        $staff = User::factory()->forRole(Role::where('code', 'staff')->firstOrFail())->create(['email' => 'used@example.com']);
        Employee::query()->forceCreate(['user_id' => $staff->id, 'employee_code' => 'USED-001', 'name' => 'Used', 'status' => EmployeeStatus::Active]);

        $this->runCommand('USED-001', 'used@example.com')->assertFailed();
        $this->assertSame(1, User::query()->count());

        $this->runCommand('ADM-001', 'admin@example.com')->assertSuccessful();
        $this->runCommand('ADM-002', 'second-admin@example.com')->assertFailed();
        $this->assertSame(1, User::query()->whereHas('role', fn ($query) => $query->where('code', 'admin'))->count());
    }

    public function test_command_recovers_when_no_valid_active_admin_remains_without_bypassing_uniqueness(): void
    {
        $oldAdmin = User::factory()->forRole(Role::where('code', 'admin')->firstOrFail())->create([
            'email' => 'old-admin@example.com', 'status' => User::STATUS_DISABLED,
        ]);
        Employee::query()->forceCreate([
            'user_id' => $oldAdmin->id, 'employee_code' => 'OLD-ADMIN', 'name' => 'Old Admin',
            'status' => EmployeeStatus::Disabled,
        ]);

        $this->runCommand('OLD-ADMIN', 'old-admin@example.com')->assertFailed();
        $this->runCommand('RECOVERY-ADMIN', 'recovery@example.com')->assertSuccessful();

        $recovery = User::where('email', 'recovery@example.com')->firstOrFail();
        $this->assertSame('admin', $recovery->role->code);
        $this->assertSame(User::STATUS_ACTIVE, $recovery->status);
        $this->assertSame(EmployeeStatus::Active, $recovery->employee->status);
        $this->assertTrue(Hash::check('Secure#Pass123', $recovery->password));
        $this->assertNotSame('Secure#Pass123', $recovery->password);
    }

    public function test_service_rolls_back_user_when_employee_insert_fails(): void
    {
        Employee::query()->forceCreate(['employee_code' => 'DUPLICATE', 'name' => 'Existing', 'status' => EmployeeStatus::Active]);

        try {
            app(CreateInitialAdminService::class)->create('DUPLICATE', 'Admin', 'rollback@example.com', 'Secure#Pass123');
            $this->fail('Expected the employee unique constraint to fail.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
        $this->assertSame(1, Employee::query()->count());
    }

    public function test_seeder_never_creates_credentials_and_command_has_no_credential_arguments(): void
    {
        $this->assertSame(0, User::query()->count());
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, User::query()->count());

        $command = app(Kernel::class)->all()['app:create-initial-admin'];
        $this->assertSame([], $command->getDefinition()->getArguments());
        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => str_contains($route->uri(), 'initial-admin')));
    }

    private function runCommand(string $code, string $email): PendingCommand
    {
        return $this->artisan('app:create-initial-admin')
            ->expectsQuestion(__('employee.bootstrap_admin.employee_code'), $code)
            ->expectsQuestion(__('employee.bootstrap_admin.employee_name'), 'Admin')
            ->expectsQuestion(__('employee.bootstrap_admin.email'), $email)
            ->expectsQuestion(__('employee.bootstrap_admin.password'), 'Secure#Pass123')
            ->expectsQuestion(__('employee.bootstrap_admin.password_confirmation'), 'Secure#Pass123');
    }
}
