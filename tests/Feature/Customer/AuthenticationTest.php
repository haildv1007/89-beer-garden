<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee(__('auth.login'))
            ->assertSee('name="_token"', false);
    }

    public function test_password_label_is_localized_in_every_supported_locale(): void
    {
        foreach (['vi' => 'Mật khẩu', 'en' => 'Password', 'zh' => '密码'] as $locale => $label) {
            app()->setLocale($locale);

            $this->get(route('login'))->assertOk()->assertSee($label)->assertDontSee('auth.labels.password');
        }
    }

    public function test_active_user_can_authenticate_and_session_is_regenerated(): void
    {
        $user = $this->createUser();
        $password = 'correct-password';

        $this->get(route('login'));
        $previousSessionId = session()->getId();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertHeader('Location', '/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $response->assertDontSee($password);
    }

    public function test_customer_can_authenticate_with_normalized_phone_while_staff_can_keep_using_email(): void
    {
        $customerUser = $this->createUser(roleCode: 'customer');
        Customer::query()->forceCreate([
            'user_id' => $customerUser->id,
            'name' => 'Khách cũ',
            'phone' => '+84901234567',
        ]);

        $this->post(route('login'), ['login' => '0901234567', 'password' => 'correct-password'])->assertRedirect(
            route('customer.home'),
        );

        $this->assertAuthenticatedAs($customerUser);
    }

    public function test_invalid_credentials_are_rejected_with_a_generic_error(): void
    {
        $user = $this->createUser();

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_disabled_account_is_rejected_with_the_same_generic_error(): void
    {
        $user = $this->createUser(status: User::STATUS_DISABLED);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'correct-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_login_input_is_validated(): void
    {
        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $key = strtolower($user->email).'|127.0.0.1';
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
        $this->assertGuest();
    }

    public function test_throttle_counter_is_shared_by_email_case_variants(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => strtoupper($user->email),
                'password' => 'wrong-password',
            ])->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post(route('login'), [
            'email' => strtolower($user->email),
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_throttle_counter_is_shared_when_email_has_surrounding_whitespace(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => '  '.$user->email."\t",
                'password' => 'wrong-password',
            ])->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_ends_authentication_and_invalidates_session(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->withSession(['private-value' => 'secret']);
        $previousSessionId = session()->getId();
        $previousToken = session()->token();

        $this->post(route('logout'))->assertRedirect(route('customer.home'));

        $this->assertGuest();
        $this->assertFalse(session()->has('private-value'));
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertNotSame($previousToken, session()->token());
    }

    public function test_internal_contexts_require_authentication_and_preserve_intended_path(): void
    {
        $user = $this->createUser();

        $this->get(route('pos.home', ['query' => 'value']))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertHeader('Location', '/pos?query=value');

        $this->get('/pos?query=value')->assertForbidden();
    }

    public function test_customer_role_remains_denied_from_every_internal_context(): void
    {
        $internalRoutes = ['pos.home', 'kitchen.home', 'admin.home'];
        $user = $this->createUser(roleCode: 'customer');

        foreach ($internalRoutes as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->get(route('customer.home'))->assertOk();
    }

    public function test_authenticated_user_cannot_view_login_page(): void
    {
        $this->actingAs($this->createUser())->get(route('login'))->assertRedirect(route('customer.home'));
    }

    public function test_disabled_authenticated_account_loses_access_on_its_next_request(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $user->forceFill(['status' => User::STATUS_DISABLED])->save();

        $this->get(route('pos.home'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_post_and_a_csrf_token(): void
    {
        $this->get('/logout')->assertMethodNotAllowed();

        app()->instance('env', 'production');

        $this->post('/logout')->assertStatus(419);
    }

    public function test_only_safe_relative_intended_destinations_are_followed(): void
    {
        $user = $this->createUser();

        $this->withSession(['url.intended' => '/pos/path?query=value'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])
            ->assertHeader('Location', '/pos/path?query=value');
    }

    public function test_unsafe_intended_redirect_variants_fall_back_to_a_relative_home_path(): void
    {
        $user = $this->createUser();
        $sameHostAbsolute = route('pos.home');
        $payloads = [
            'https://evil.example/steal',
            $sameHostAbsolute,
            '//evil.example/steal',
            '\\evil.example/steal',
            '/\\evil.example/steal',
            '/%2F%2Fevil.example/steal',
            '/%5Cevil.example/steal',
            '/%252F%252Fevil.example/steal',
            'hTtPs://evil.example/steal',
            "/pos\r\nLocation: https://evil.example/steal",
        ];

        foreach ($payloads as $payload) {
            $this->withSession(['url.intended' => $payload])
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'correct-password',
                    'redirect' => $payload,
                ])
                ->assertHeader('Location', '/');

            $this->post(route('logout'))->assertRedirect(route('customer.home'));
        }
    }

    public function test_spoofed_host_cannot_turn_an_absolute_intended_url_into_a_valid_redirect(): void
    {
        $user = $this->createUser();

        $this->withHeader('Host', 'evil.example')
            ->withSession(['url.intended' => 'https://evil.example/admin'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])
            ->assertHeader('Location', '/');
    }

    public function test_trusted_hosts_are_derived_from_the_configured_application_url(): void
    {
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $originalEnvironment = app()->environment();

        $this->assertIsString($configuredHost);
        $this->assertNotSame('', $configuredHost);
        $this->assertContains(TrustHosts::class, app(HttpKernel::class)->getGlobalMiddleware());

        app()->instance('env', 'production');

        try {
            $middleware = app(TrustHosts::class);
            $trustedRequest = Request::create('http://'.$configuredHost.'/login');

            $middleware->handle($trustedRequest, function (Request $request) use ($configuredHost): Response {
                $this->assertSame($configuredHost, $request->getHost());

                return new Response;
            });

            $untrustedRequest = Request::create('http://evil.example/login');

            try {
                $middleware->handle($untrustedRequest, function (Request $request): Response {
                    $request->getHost();

                    return new Response;
                });

                $this->fail('The spoofed host was not rejected.');
            } catch (SuspiciousOperationException $exception) {
                $this->assertStringContainsString('Untrusted Host', $exception->getMessage());
            }
        } finally {
            app()->instance('env', $originalEnvironment);
        }
    }

    public function test_login_tracking_failure_clears_partial_authentication_and_rethrows(): void
    {
        $user = $this->createUser();
        $expectedException = new RuntimeException('Login tracking failed.');

        Event::listen('eloquent.saving: '.User::class, function (User $savingUser) use ($expectedException): void {
            if ($savingUser->isDirty('last_login_at')) {
                throw $expectedException;
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ]);

            $this->fail('The login tracking exception was not rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame($expectedException, $exception);
        }

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    private function createUser(string $status = User::STATUS_ACTIVE, string $roleCode = 'customer'): User
    {
        $role = Role::query()->firstOrCreate(['code' => $roleCode], ['name' => ucfirst($roleCode)]);

        return User::factory()
            ->forRole($role)
            ->create([
                'password' => 'correct-password',
                'status' => $status,
            ]);
    }
}
