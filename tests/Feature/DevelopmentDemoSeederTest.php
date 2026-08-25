<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\RestaurantTableStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DevelopmentDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $demoPassword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->demoPassword = 'T9#'.Str::random(16).'aA';
        $this->setDemoPassword($this->demoPassword);
    }

    protected function tearDown(): void
    {
        $this->setDemoPassword(null);
        parent::tearDown();
    }

    public function test_seeder_refuses_every_environment_except_local_and_testing(): void
    {
        app()->instance('env', 'production');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('local or testing');
            app(DevelopmentDemoSeeder::class)->run();
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_missing_or_invalid_password_stops_before_any_demo_record_is_created(): void
    {
        foreach ([null, 'too-short'] as $password) {
            $this->setDemoPassword($password);

            try {
                $this->seed(DevelopmentDemoSeeder::class);
                $this->fail('The seeder should reject a missing or invalid demo password.');
            } catch (RuntimeException) {
                $this->assertSame(0, Category::query()->where('slug', 'like', 'demo-%')->count());
                $this->assertSame(0, User::query()->where('email', 'like', '%.demo@89beergarden.test')->count());
            }
        }
    }

    public function test_seeder_creates_purposeful_consistent_demo_data(): void
    {
        $this->seed(DevelopmentDemoSeeder::class);

        $this->assertSame(6, Category::query()->where('slug', 'like', 'demo-%')->count());
        $this->assertSame(1, Category::query()->where('slug', 'like', 'demo-%')->where('status', Category::STATUS_INACTIVE)->count());
        $this->assertSame(36, Product::query()->where('slug', 'like', 'demo-%')->count());
        $this->assertSame(31, Product::query()->where('slug', 'like', 'demo-%')->where('status', Product::STATUS_ACTIVE)->where('is_available', true)->count());
        $this->assertSame(3, Product::query()->where('slug', 'like', 'demo-%')->where('status', Product::STATUS_ACTIVE)->where('is_available', false)->count());
        $this->assertSame(2, Product::query()->where('slug', 'like', 'demo-%')->where('status', Product::STATUS_INACTIVE)->count());
        $this->assertSame(28, Product::query()->publicMenu()->where('products.slug', 'like', 'demo-%')->count());

        $this->assertSame(20, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(3, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->where('runtime_status', RestaurantTableStatus::Cleaning)->count());
        $this->assertSame(17, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->where('runtime_status', RestaurantTableStatus::Available)->count());
        $this->assertSame(1, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->where('is_active', false)->where('runtime_status', RestaurantTableStatus::Available)->count());
        $this->assertSame(0, DB::table('dining_sessions')->count());
        $this->assertSame(0, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->whereIn('runtime_status', [RestaurantTableStatus::Occupied, RestaurantTableStatus::Reserved])->count());

        $this->assertSame(12, Customer::query()->where('email', 'like', '%.demo@89beergarden.test')->count());
        $this->assertSame(9, Customer::query()->where('email', 'like', 'guest.%.demo@89beergarden.test')->whereNull('user_id')->count());
        $this->assertSame(3, Customer::query()->where('email', 'like', 'customer.%.demo@89beergarden.test')->whereNotNull('user_id')->count());
        $this->assertSame(4, Employee::query()->where('employee_code', 'like', 'DEMO-%')->count());

        foreach (['admin', 'manager', 'staff', 'kitchen'] as $roleCode) {
            $user = User::query()->where('email', "{$roleCode}.demo@89beergarden.test")->firstOrFail();
            $this->assertSame($roleCode, $user->role->code);
            $this->assertSame(User::STATUS_ACTIVE, $user->status);
            $this->assertSame(1, $user->employee()->active()->count());
            $this->assertNull($user->customer);
            $this->assertTrue(Hash::check($this->demoPassword, $user->password));
            $this->assertNotSame($this->demoPassword, $user->password);
        }

        foreach (User::query()->where('email', 'like', 'customer.%.demo@89beergarden.test')->get() as $user) {
            $this->assertSame('customer', $user->role->code);
            $this->assertSame(User::STATUS_ACTIVE, $user->status);
            $this->assertNotNull($user->customer);
            $this->assertNull($user->employee);
        }

        $this->assertSame(0, DB::table('reservations')->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('order_items')->count());
    }

    public function test_running_twice_is_idempotent_preserves_passwords_permissions_and_initial_admin(): void
    {
        $initialAdmin = User::query()->forceCreate([
            'email' => 'initial.owner@example.test',
            'password' => Hash::make('I9#'.Str::random(16).'aA'),
            'role_id' => Role::query()->where('code', 'admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);
        $initialEmployee = Employee::query()->forceCreate([
            'user_id' => $initialAdmin->id,
            'employee_code' => 'INITIAL-ADMIN',
            'name' => 'Initial Owner',
            'status' => EmployeeStatus::Active,
        ]);
        $initialSnapshot = $initialAdmin->only(['email', 'password', 'role_id', 'status', 'last_login_at']);
        $employeeSnapshot = $initialEmployee->only(['user_id', 'employee_code', 'name', 'phone', 'position', 'status']);
        $permissionSnapshot = Permission::query()->orderBy('id')->get(['id', 'code', 'name', 'description'])->toArray();
        $matrixSnapshot = DB::table('role_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray();

        $this->seed(DevelopmentDemoSeeder::class);
        $countsAfterFirstRun = $this->demoCounts();
        $passwordsAfterFirstRun = User::query()->where('email', 'like', '%.demo@89beergarden.test')->orderBy('email')->pluck('password', 'email')->all();
        $this->seed(DevelopmentDemoSeeder::class);

        $this->assertSame($countsAfterFirstRun, $this->demoCounts());
        $this->assertSame($passwordsAfterFirstRun, User::query()->where('email', 'like', '%.demo@89beergarden.test')->orderBy('email')->pluck('password', 'email')->all());
        $this->assertSame($permissionSnapshot, Permission::query()->orderBy('id')->get(['id', 'code', 'name', 'description'])->toArray());
        $this->assertEquals($matrixSnapshot, DB::table('role_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray());
        $this->assertSame($initialSnapshot, $initialAdmin->fresh()->only(array_keys($initialSnapshot)));
        $this->assertSame($employeeSnapshot, $initialEmployee->fresh()->only(array_keys($employeeSnapshot)));
    }

    public function test_failure_rolls_back_all_demo_records_from_that_run(): void
    {
        $conflictingUser = User::query()->forceCreate([
            'email' => 'customer.lan.demo@89beergarden.test',
            'password' => Hash::make('C9#'.Str::random(16).'aA'),
            'role_id' => Role::query()->where('code', 'customer')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);
        Employee::query()->forceCreate([
            'user_id' => $conflictingUser->id,
            'employee_code' => 'PREEXISTING-EMPLOYEE',
            'name' => 'Pre-existing employee',
            'status' => EmployeeStatus::Active,
        ]);

        try {
            $this->seed(DevelopmentDemoSeeder::class);
            $this->fail('The conflicting account should abort the demo seed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already linked to an employee', $exception->getMessage());
        }

        $this->assertSame(0, Category::query()->where('slug', 'like', 'demo-%')->count());
        $this->assertSame(0, Product::query()->where('slug', 'like', 'demo-%')->count());
        $this->assertSame(0, RestaurantTable::query()->where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(0, Customer::query()->where('email', 'like', '%.demo@89beergarden.test')->count());
        $this->assertSame(1, User::query()->where('email', 'customer.lan.demo@89beergarden.test')->count());
        $this->assertSame(1, Employee::query()->where('employee_code', 'PREEXISTING-EMPLOYEE')->count());
    }

    public function test_demo_data_is_visible_through_public_pages_without_weakening_internal_authorization(): void
    {
        $this->seed(DevelopmentDemoSeeder::class);

        $this->get('/')->assertOk()->assertSee(__('app.home.heading'))->assertDontSee('Nước suối');
        $this->get('/menu')->assertOk()->assertSee('Bia &amp; Đồ uống có cồn', false)->assertSee('Bia Sài Gòn Lager')
            ->assertSee(__('app.products.unavailable'))->assertDontSee('Dồi sụn nướng')->assertDontSee('Nước suối');
        $this->get('/login')->assertOk();

        $customer = User::query()->where('email', 'customer.lan.demo@89beergarden.test')->firstOrFail();
        $this->actingAs($customer)->get(route('customer.profile.show', $customer->customer))
            ->assertOk()->assertDontSee('Khách quen, ưu tiên bàn ngoài trời khi còn chỗ.');
        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($customer)->get('/pos/tables')->assertForbidden();
    }

    /** @return array<string, int> */
    private function demoCounts(): array
    {
        return [
            'categories' => Category::query()->where('slug', 'like', 'demo-%')->count(),
            'products' => Product::query()->where('slug', 'like', 'demo-%')->count(),
            'tables' => RestaurantTable::query()->where('code', 'like', 'DEMO-%')->count(),
            'customers' => Customer::query()->where('email', 'like', '%.demo@89beergarden.test')->count(),
            'users' => User::query()->where('email', 'like', '%.demo@89beergarden.test')->count(),
            'employees' => Employee::query()->where('employee_code', 'like', 'DEMO-%')->count(),
        ];
    }

    private function setDemoPassword(?string $password): void
    {
        if ($password === null) {
            unset($_ENV['DEMO_USER_PASSWORD'], $_SERVER['DEMO_USER_PASSWORD']);
            putenv('DEMO_USER_PASSWORD');

            return;
        }

        $_ENV['DEMO_USER_PASSWORD'] = $password;
        $_SERVER['DEMO_USER_PASSWORD'] = $password;
        putenv("DEMO_USER_PASSWORD={$password}");
    }
}
