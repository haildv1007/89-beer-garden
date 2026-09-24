<?php

namespace Tests\Feature\Customer;

use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_forgot_and_reset_password_pages(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSee(__('auth.password_reset.request_title'));

        $this->get(route('password.reset', ['token' => 'example-token', 'email' => 'guest@example.com']))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertSee('value="guest@example.com"', false);
    }

    public function test_active_user_receives_a_localized_smtp_password_reset_notification(): void
    {
        Notification::fake();
        app()->setLocale('vi');
        $user = $this->createUser('Member@Example.com');

        $this->post(route('password.email'), ['email' => '  MEMBER@example.com '])
            ->assertRedirect()
            ->assertSessionHas('success', __('auth.password_reset.link_sent'));

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $message = $notification->toMail($user);

                $this->assertInstanceOf(MailMessage::class, $message);
                $this->assertSame('Đặt lại mật khẩu — 89 Beer Garden', $message->subject);
                $this->assertStringContainsString($notification->token, $message->actionUrl);
                $this->assertStringContainsString(urlencode($user->email), $message->actionUrl);

                return true;
            },
        );

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_unknown_and_disabled_accounts_receive_the_same_generic_response_without_email(): void
    {
        Notification::fake();
        $disabled = $this->createUser('disabled@example.com', User::STATUS_DISABLED);
        $expected = __('auth.password_reset.link_sent');

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertSessionHas('success', $expected);
        $this->post(route('password.email'), ['email' => $disabled->email])
            ->assertSessionHas('success', $expected);

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $disabled->email]);
    }

    public function test_valid_token_resets_password_rotates_remember_token_and_can_only_be_used_once(): void
    {
        Notification::fake();
        $user = $this->createUser('member@example.com');
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $newPassword = 'New#Password123';
        $payload = [
            'token' => $token,
            'email' => strtoupper($user->email),
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        $this->post(route('password.update'), $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', __('auth.password_reset.success'));

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertNotNull($user->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.update'), $payload)
            ->assertSessionHasErrors(['email' => __('auth.password_reset.invalid_token')]);
    }

    public function test_password_policy_and_invalid_token_are_rejected_without_changing_password(): void
    {
        $user = $this->createUser('member@example.com');
        $originalPassword = $user->password;

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $strongPassword = 'Another#Password123';
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => $strongPassword,
            'password_confirmation' => $strongPassword,
        ])->assertSessionHasErrors(['email' => __('auth.password_reset.invalid_token')]);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    private function createUser(string $email, string $status = User::STATUS_ACTIVE): User
    {
        $role = Role::query()->firstOrCreate(['code' => 'customer'], ['name' => 'Customer']);

        return User::factory()->forRole($role)->create([
            'email' => $email,
            'password' => 'Current#Password123',
            'status' => $status,
        ]);
    }
}
