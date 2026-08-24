<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Http\Middleware\AuthorizeInternalContext;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InternalContextAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login_for_every_internal_context(): void
    {
        foreach ($this->internalRoutes() as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_context_access_follows_the_approved_permission_matrix(): void
    {
        $matrix = [
            'customer' => [403, 403, 403],
            'staff' => [200, 403, 403],
            'kitchen' => [403, 200, 403],
            'manager' => [200, 200, 200],
            'admin' => [200, 200, 200],
        ];

        foreach ($matrix as $role => $expectedStatuses) {
            $user = $this->createUser($role, employee: $role !== 'customer');

            foreach (array_values($this->internalRoutes()) as $index => $route) {
                $this->actingAs($user)
                    ->get(route($route))
                    ->assertStatus($expectedStatuses[$index]);
            }
        }

        $customer = $this->createUser('customer');
        $this->actingAs($customer)->get(route('customer.home'))->assertOk();
    }

    public function test_internal_context_requires_an_active_employee_profile(): void
    {
        $missingEmployee = $this->createUser('manager');
        $disabledEmployee = $this->createUser('manager', employee: true, employeeStatus: EmployeeStatus::Disabled);

        foreach ($this->internalRoutes() as $route) {
            $this->actingAs($missingEmployee)->get(route($route))->assertForbidden();
            $this->actingAs($disabledEmployee)->get(route($route))->assertForbidden();
        }

        $this->assertDenialPreservesSession($missingEmployee, route('pos.home'));
        $this->assertDenialPreservesSession($disabledEmployee, route('pos.home'));
        $this->assertMiddlewareDenialPreservesSession($missingEmployee, 'pos');
        $this->assertMiddlewareDenialPreservesSession($disabledEmployee, 'pos');
    }

    public function test_authorization_denial_does_not_logout_or_mutate_the_session(): void
    {
        $user = $this->createUser('manager', employee: true, employeeStatus: EmployeeStatus::Disabled);
        $this->actingAs($user)->withSession(['authorization-marker' => 'preserved']);

        $this->get(route('pos.home'))->assertForbidden();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('preserved', session('authorization-marker'));
    }

    public function test_permission_revocation_applies_on_the_next_request_without_logging_out(): void
    {
        $user = $this->createUser('staff', employee: true);

        $this->actingAs($user)
            ->withSession(['authorization-marker' => 'preserved'])
            ->get(route('pos.home'))
            ->assertOk();
        $user->role->permissions()->detach(
            Permission::query()->where('code', 'context.pos.access')->firstOrFail(),
        );

        $this->get(route('pos.home'))->assertForbidden();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('preserved', session('authorization-marker'));
        $this->assertMiddlewareDenialPreservesSession($user, 'pos');
    }

    public function test_context_middleware_accepts_only_case_sensitive_approved_identifiers(): void
    {
        $manager = $this->createUser('manager', employee: true);

        foreach (['pos', 'kitchen', 'admin'] as $context) {
            $uri = $this->registerContextProbe('valid-'.$context, $context);

            $this->actingAs($manager)->get($uri)->assertOk();
        }

        foreach ([null, '', 'unknown', 'POS', 'pos.extra'] as $index => $context) {
            $uri = $this->registerContextProbe('invalid-'.$index, $context);

            $this->assertDenialPreservesSession($manager, $uri);
            $this->assertMiddlewareDenialPreservesSession($manager, $context);
        }

        $customerWithEmployee = $this->createUser('customer', employee: true);
        $nonContextUri = $this->registerContextProbe(
            'valid-permission-but-not-context',
            'customer.profile.manage-own',
        );

        $this->assertTrue($customerWithEmployee->can('customer.profile.manage-own'));
        $this->assertDenialPreservesSession($customerWithEmployee, $nonContextUri);
        $this->assertMiddlewareDenialPreservesSession(
            $customerWithEmployee,
            'customer.profile.manage-own',
        );
    }

    public function test_role_name_alone_cannot_bypass_permissions(): void
    {
        $role = Role::query()->create(['code' => 'unprivileged', 'name' => 'Admin']);
        $user = User::factory()->forRole($role)->create();
        $this->createEmployee($user);

        foreach ($this->internalRoutes() as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_customer_view_is_denied_to_staff_and_allowed_only_to_management_roles(): void
    {
        $staff = $this->createUser('staff', employee: true);
        $manager = $this->createUser('manager', employee: true);
        $admin = $this->createUser('admin', employee: true);

        $this->assertFalse($staff->can('customer.view'));
        $this->assertTrue($manager->can('customer.view'));
        $this->assertTrue($admin->can('customer.view'));
    }

    public function test_unknown_gate_ability_fails_closed_for_every_actor(): void
    {
        foreach (['customer', 'staff', 'kitchen', 'manager', 'admin'] as $role) {
            $user = $this->createUser($role, employee: $role !== 'customer');

            $this->assertFalse(Gate::forUser($user)->allows('unknown.permission'));
        }

        $this->assertFalse(Gate::forUser(null)->allows('unknown.permission'));
    }

    public function test_every_sensitive_capability_has_permission_based_allow_and_deny_behavior(): void
    {
        $sensitive = [
            'product.update-price',
            'employee.disable',
            'permission.assign',
            'order-item.cancel-waiting',
            'order-item.cancel-preparing',
            'payment.complete',
            'inventory.stock-movement.create',
            'translation.update',
            'settings.update',
        ];
        $admin = $this->createUser('admin', employee: true);

        foreach ($sensitive as $permission) {
            $this->assertTrue(Gate::forUser($admin)->allows($permission));

            $admin->role->permissions()->detach(
                Permission::query()->where('code', $permission)->firstOrFail(),
            );

            $this->assertFalse(Gate::forUser($admin)->allows($permission));
        }
    }

    public function test_navigation_visibility_uses_the_same_backend_permissions(): void
    {
        $manager = $this->createUser('manager', employee: true);
        $staff = $this->createUser('staff', employee: true);
        $disabledManager = $this->createUser('manager', employee: true, employeeStatus: EmployeeStatus::Disabled);

        $this->actingAs($manager)
            ->get(route('customer.home'))
            ->assertSee(route('pos.home'))
            ->assertSee(route('kitchen.home'))
            ->assertSee(route('admin.home'));

        $this->actingAs($staff)
            ->get(route('customer.home'))
            ->assertSee(route('pos.home'))
            ->assertDontSee(route('kitchen.home'))
            ->assertDontSee(route('admin.home'));

        $this->actingAs($disabledManager)
            ->get(route('customer.home'))
            ->assertDontSee(route('pos.home'))
            ->assertDontSee(route('kitchen.home'))
            ->assertDontSee(route('admin.home'));
    }

    /** @return array<string, string> */
    private function internalRoutes(): array
    {
        return [
            'pos' => 'pos.home',
            'kitchen' => 'kitchen.home',
            'admin' => 'admin.home',
        ];
    }

    private function createUser(
        string $roleCode,
        bool $employee = false,
        EmployeeStatus $employeeStatus = EmployeeStatus::Active,
    ): User {
        $user = User::factory()
            ->forRole(Role::query()->where('code', $roleCode)->firstOrFail())
            ->create();

        if ($employee) {
            $this->createEmployee($user, $employeeStatus);
        }

        return $user;
    }

    private function createEmployee(User $user, EmployeeStatus $status = EmployeeStatus::Active): Employee
    {
        return Employee::forceCreate([
            'user_id' => $user->getKey(),
            'employee_code' => 'E-'.str_pad((string) $user->getKey(), 5, '0', STR_PAD_LEFT),
            'name' => 'Employee '.$user->getKey(),
            'status' => $status,
        ]);
    }

    private function registerContextProbe(string $suffix, ?string $context): string
    {
        $middleware = AuthorizeInternalContext::class;

        if ($context !== null) {
            $middleware .= ':'.$context;
        }

        $uri = '/_context-authorization-probe/'.$suffix;

        Route::middleware(['web', 'auth', $middleware])
            ->get($uri, fn () => response('allowed'));

        return $uri;
    }

    private function assertDenialPreservesSession(User $user, string $uri): void
    {
        $marker = 'preserved-'.$user->getKey();
        $this->actingAs($user)->withSession(['authorization-marker' => $marker]);

        $this->get($uri)->assertForbidden();

        $this->assertAuthenticatedAs($user);
        $this->assertSame($marker, session('authorization-marker'));
    }

    private function assertMiddlewareDenialPreservesSession(User $user, ?string $context): void
    {
        $session = new Store('authorization-test', new ArraySessionHandler(120));
        $session->start();
        $session->put('authorization-marker', 'preserved');
        $sessionId = $session->getId();
        $request = Request::create('/internal-context');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        try {
            app(AuthorizeInternalContext::class)->handle(
                $request,
                fn () => response('unexpectedly allowed'),
                $context,
            );
            $this->fail('Context middleware unexpectedly allowed a denied request.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('preserved', $session->get('authorization-marker'));
        $this->assertSame($sessionId, $session->getId());
    }
}
