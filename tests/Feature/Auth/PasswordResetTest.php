<?php

namespace Tests\Feature\Auth;

use App\Models\Guest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'abcdefghijklmnopqrst';
    private string $password = 'Password1!';

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $guest = Guest::factory()->create([
            'email' => 'guest@example.com',
        ]);

        $response = $this->post('/api/guest/forgot-password', [
            'email' => $guest->email,
        ]);

        Notification::assertSentTo($guest, ResetPassword::class);
        $response
            ->assertOk()
            ->assertJson([
                'message' => __(Password::RESET_LINK_SENT),
            ]);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $guest = $this->createGuestWithPasswordResetToken();

        Event::fake();

        $response = $this->post('/api/guest/reset-password', [
            'id' => $guest->id,
            'token' => $this->token,
            'password' => $this->password,
        ]);

        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event) => $event->user->is($guest));
        $this->assertTrue(Hash::check($this->password, $guest->fresh()->password));
        $this->assertArrayNotHasKey('pw_reset_token', $guest->fresh()->data);
        $this->assertArrayNotHasKey('pw_reset_exp', $guest->fresh()->data);
        $response
            ->assertOk()
            ->assertJson([
                'message' => __('Your password has been updated'),
            ]);
    }

    public function test_password_can_not_be_reset_with_invalid_token(): void
    {
        $guest = $this->createGuestWithPasswordResetToken();

        Event::fake();

        $response = $this->post('/api/guest/reset-password', [
            'id' => $guest->id,
            'token' => 'wrongwrongwrongwrong',
            'password' => $this->password,
        ]);

        Event::assertNotDispatched(PasswordReset::class);
        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => __('The password reset link is invalid or it has expired, please request a new one..'),
            ]);
    }

    public function test_password_can_not_be_reset_with_expired_token(): void
    {
        $guest = $this->createGuestWithPasswordResetToken([
            'pw_reset_exp' => time() - 1,
        ]);

        Event::fake();

        $response = $this->post('/api/guest/reset-password', [
            'id' => $guest->id,
            'token' => $this->token,
            'password' => $this->password,
        ]);

        Event::assertNotDispatched(PasswordReset::class);
        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => __('The password reset link is invalid or it has expired, please request a new one.'),
            ]);
    }

    public function test_reset_password_link_can_not_be_requested_for_unknown_guest(): void
    {
        Notification::fake();

        $response = $this->post('/api/guest/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        Notification::assertNothingSent();
        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => __('Invalid data #3'),
            ]);
    }

    private function createGuestWithPasswordResetToken(array $overrides = []): Guest
    {
        $guest = Guest::factory()->create();

        $guest->forceFill([
            'data' => [
                'pw_reset_token' => $overrides['pw_reset_token'] ?? $this->token,
                'pw_reset_exp' => $overrides['pw_reset_exp'] ?? time() + 3600,
            ],
        ])->save();

        return $guest;
    }
}
