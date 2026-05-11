<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_authenticate_using_the_login_screen(): void
    {
        $guest = Guest::factory()->create([
            'email' => 'guest@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->post('/api/guest/login', [
            'email' => $guest->email,
            'password' => 'Password1!',
        ]);

        $this->assertAuthenticated('guard_guest');
        $response
            ->assertOk()
            ->assertJsonStructure([
                'user',
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_guests_can_not_authenticate_with_invalid_password(): void
    {
        $guest = Guest::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->post('/api/guest/login', [
            'email' => $guest->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertGuest('guard_guest');
    }

    public function test_guests_can_logout(): void
    {
        $guest = Guest::factory()->create([
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->post('/api/guest/login', [
            'email' => $guest->email,
            'password' => 'Password1!',
        ]);

        $response = $this
            ->withToken($loginResponse->json('access_token'))
            ->post('/api/guest/logout');

        $this->assertGuest('guard_guest');
        $response
            ->assertOk()
            ->assertJson([
                'message' => __('Successfully logged out'),
            ]);
    }

    public function test_staff_can_authenticate_using_the_login_screen(): void
    {
        $staff = Employee::factory()->create([
            'email' => 'staff@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->post('/api/staff/login', [
            'email' => $staff->email,
            'password' => 'Password1!',
        ]);

        $this->assertAuthenticated('guard_employee');
        $response
            ->assertOk()
            ->assertJsonStructure([
                'user',
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_staff_can_not_authenticate_with_invalid_password(): void
    {
        $staff = Employee::factory()->create();

        $this->post('/api/staff/login', [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertGuest('guard_employee');
    }

    public function test_staff_can_logout(): void
    {
        $staff = Employee::factory()->create();

        $loginResponse = $this->post('/api/staff/login', [
            'email' => $staff->email,
            'password' => 'Password1!',
        ]);

        $response = $this
            ->withToken($loginResponse->json('access_token'))
            ->post('/api/staff/logout');

        $this->assertGuest('guard_employee');
        $response
            ->assertOk()
            ->assertJson([
                'message' => __('Successfully logged out'),
            ]);
    }
}
