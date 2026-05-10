<?php

namespace Tests\Unit;

use App\Http\Controllers\GuestController;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GuestControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $oldPassword = 'Oldpassword123!';
    private string $newPassword = 'SnS-Unit-Password-2026-7f4Q!';

    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldUse('guard_guest');
    }

    /** @test */
    public function update_password_hashes_new_password()
    {
        // Arrange
        $guest = Guest::factory()->create();
        Auth::login($guest);

        $request = Request::create('/update-password', 'POST', [
            'current_password' => $this->oldPassword,
            'password' => $this->newPassword,
            'password_confirmation' => $this->newPassword,
        ]);

        // Mock current password check (since we can't easily set it)
        $guest->password = Hash::make($this->oldPassword);
        $guest->save();

        // Act
        $controller = new GuestController();
        $response = $controller->updatePassword($request);

        // Assert
        $this->assertEquals($guest->id, $response->id);
        $this->assertTrue(Hash::check($this->newPassword, $guest->fresh()->password));
    }

    /** @test */
    public function update_password_validates_current_password()
    {
        // Arrange
        $guest = Guest::factory()->create();
        Auth::login($guest);

        $guest->password = Hash::make($this->oldPassword);
        $guest->save();

        $request = Request::create('/update-password', 'POST', [
            'current_password' => 'wrongpassword',
            'password' => $this->newPassword,
            'password_confirmation' => $this->newPassword,
        ]);

        // Act & Assert
        $controller = new GuestController();
        $this->expectException(ValidationException::class);
        $controller->updatePassword($request);
    }

    /** @test */
    public function update_password_requires_confirmation()
    {
        // Arrange
        $guest = Guest::factory()->create();
        Auth::login($guest);

        $guest->password = Hash::make($this->oldPassword);
        $guest->save();

        $request = Request::create('/update-password', 'POST', [
            'current_password' => $this->oldPassword,
            'password' => $this->newPassword,
            'password_confirmation' => 'differentpassword',
        ]);

        // Act & Assert
        $controller = new GuestController();
        $this->expectException(ValidationException::class);
        $controller->updatePassword($request);
    }
}
