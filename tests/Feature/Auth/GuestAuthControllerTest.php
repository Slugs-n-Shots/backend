<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\GuestAuthController;
use App\Models\Guest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class GuestAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'abcdefghijklmnopqrst';
    private string $password = 'Password1!';

    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldUse('guard_guest');
    }

    /** @test */
    public function forgot_password_uses_exists_instead_of_get()
    {
        // Arrange
        $guest = Guest::factory()->create(['email' => 'test@example.com']);
        $request = Request::create('/forgot-password', 'POST', ['email' => 'test@example.com']);

        // Mock Password facade
        Password::shouldReceive('broker->sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->forgotPassword($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }

    /** @test */
    public function forgot_password_returns_error_for_nonexistent_email()
    {
        // Arrange
        $request = Request::create('/forgot-password', 'POST', ['email' => 'nonexistent@example.com']);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->forgotPassword($request);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame(__('Invalid data #3'), $response->getData(true)['message']);
    }

    /** @test */
    public function reset_password_uses_isset_for_token_validation()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $guest->data = ['pw_reset_token' => $this->token, 'pw_reset_exp' => time() + 3600];
        $guest->save();

        $request = Request::create('/reset-password', 'POST', [
            'id' => $guest->id,
            'token' => $this->token,
            'password' => $this->password,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->resetPassword($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(__('Your password has been updated'), $response->getData(true)['message']);
        $this->assertTrue(Hash::check($this->password, $guest->fresh()->password));
    }

    /** @test */
    public function reset_password_fails_if_token_not_set()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $guest->data = []; // No token/exp set
        $guest->save();

        $request = Request::create('/reset-password', 'POST', [
            'id' => $guest->id,
            'token' => $this->token,
            'password' => $this->password,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->resetPassword($request);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame(__('Invalid user or token'), $response->getData(true)['message']);
    }

    /** @test */
    public function register_hashes_password()
    {
        // Arrange
        Event::fake();
        $request = Request::create('/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => $this->password,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->register($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $guest = Guest::where('email', 'john@example.com')->first();
        $this->assertNotNull($guest);
        $this->assertTrue(Hash::check($this->password, $guest->password));
        Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
    }

    /** @test */
    public function confirm_registration_uses_isset_for_token_validation()
    {
        // Arrange
        $guest = Guest::factory()->create();
        Event::fake();

        $guest->data = ['confirm_token' => $this->token, 'confirm_exp' => time() + 3600];
        $guest->save();

        $request = Request::create('/confirm-registration', 'POST', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->confirmRegistration($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(__('Your account has been activated'), $response->getData(true)['message']);
        Event::assertDispatched(Verified::class);
    }

    /** @test */
    public function confirm_registration_fails_if_token_not_set()
    {
        // Arrange
        $guest = Guest::factory()->create();
        $guest->data = []; // No token/exp set
        $guest->save();

        $request = Request::create('/confirm-registration', 'POST', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->confirmRegistration($request);

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame(__('Invalid user or token'), $response->getData(true)['message']);
    }

    /** @test */
    public function confirm_registration_skips_if_already_verified()
    {
        // Arrange
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $guest->data = ['confirm_token' => $this->token, 'confirm_exp' => time() + 3600];
        $guest->save();

        $request = Request::create('/confirm-registration', 'POST', [
            'id' => $guest->id,
            'token' => $this->token,
        ]);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->confirmRegistration($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(__('Your email is already verified'), $response->getData(true)['message']);
    }

    /** @test */
    public function resend_email_verification_returns_json_response()
    {
        // Arrange
        Notification::fake();
        $guest = Guest::factory()->create(['email' => 'test@example.com']);
        $request = Request::create('/resend-verification', 'POST', ['email' => 'test@example.com']);

        // Act
        $controller = new GuestAuthController();
        $response = $controller->resendEmailVerificationMail($request);

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('message', $response->getData(true));
    }
}
