<?php

namespace Tests\Feature\Auth;

use App\Models\Guest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'abcdefghijklmnopqrst';

    public function test_email_can_be_verified(): void
    {
        $guest = $this->createUnverifiedGuestWithConfirmationToken();

        Event::fake();

        $response = $this->post('/api/guest/confirm-registration', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        Event::assertDispatched(Verified::class, fn (Verified $event) => $event->user->is($guest));
        $this->assertTrue($guest->fresh()->hasVerifiedEmail());
        $this->assertArrayNotHasKey('confirm_token', $guest->fresh()->data);
        $this->assertArrayNotHasKey('confirm_exp', $guest->fresh()->data);
        $response
            ->assertOk()
            ->assertJson([
                'message' => __('Your account has been activated'),
            ]);
    }

    public function test_email_is_not_verified_with_invalid_token(): void
    {
        $guest = $this->createUnverifiedGuestWithConfirmationToken();

        Event::fake();

        $response = $this->post('/api/guest/confirm-registration', [
            'id' => $guest->id,
            'token' => 'wrongwrongwrongwrong',
        ]);

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($guest->fresh()->hasVerifiedEmail());
        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => __('The confirmation link is invalid or it has expired, please request a new one..'),
            ]);
    }

    public function test_email_is_not_verified_with_expired_token(): void
    {
        $guest = $this->createUnverifiedGuestWithConfirmationToken([
            'confirm_exp' => time() - 1,
        ]);

        Event::fake();

        $response = $this->post('/api/guest/confirm-registration', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($guest->fresh()->hasVerifiedEmail());
        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => __('The confirmation link is invalid or it has expired, please request a new one.'),
            ]);
    }

    public function test_already_verified_guest_is_not_verified_again(): void
    {
        $guest = $this->createUnverifiedGuestWithConfirmationToken([
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $response = $this->post('/api/guest/confirm-registration', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        Event::assertNotDispatched(Verified::class);
        $response
            ->assertOk()
            ->assertJson([
                'message' => __('Your email is already verified'),
            ]);
    }

    private function createUnverifiedGuestWithConfirmationToken(array $overrides = []): Guest
    {
        $guest = Guest::factory()->create([
            'email_verified_at' => $overrides['email_verified_at'] ?? null,
        ]);

        $guest->forceFill([
            'data' => [
                'confirm_token' => $overrides['confirm_token'] ?? $this->token,
                'confirm_exp' => $overrides['confirm_exp'] ?? time() + 3600,
            ],
        ])->save();

        return $guest;
    }
}
