<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\GuestController;
use App\Models\Employee;
use App\Models\Guest;
use App\Models\GdprAuditEvent;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    /** @test */
    public function guest_can_upload_profile_picture()
    {
        Storage::fake('public');
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $response = $this
            ->actingAs($guest, 'guard_guest')
            ->post('/api/guest/me/picture', [
                'picture' => UploadedFile::fake()->create('avatar.png', 256, 'image/png'),
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('id', $guest->id);

        $path = $response->json('picture');
        $this->assertStringStartsWith("guest-pictures/{$guest->id}/", $path);
        $this->assertSame($path, $guest->fresh()->picture);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function guest_can_upload_jpeg_profile_picture()
    {
        Storage::fake('public');
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $response = $this
            ->actingAs($guest, 'guard_guest')
            ->post('/api/guest/me/picture', [
                'picture' => UploadedFile::fake()->image('avatar.jpg', 320, 320)->size(256),
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('id', $guest->id);

        $path = $response->json('picture');
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame($path, $guest->fresh()->picture);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function guest_picture_upload_requires_an_image()
    {
        Storage::fake('public');
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->post('/api/guest/me/picture', [
                'picture' => UploadedFile::fake()->create('avatar.txt', 16, 'text/plain'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertNull($guest->fresh()->picture);
    }

    /** @test */
    public function guest_picture_upload_uses_configured_max_size()
    {
        config(['guests.profile_picture_max_kilobytes' => 100]);
        Storage::fake('public');
        $guest = Guest::factory()->create(['email_verified_at' => now()]);

        $this
            ->actingAs($guest, 'guard_guest')
            ->post('/api/guest/me/picture', [
                'picture' => UploadedFile::fake()->create('avatar.png', 101, 'image/png'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertNull($guest->fresh()->picture);
    }

    /** @test */
    public function guest_can_delete_profile_picture()
    {
        Storage::fake('public');
        $guest = Guest::factory()->create([
            'email_verified_at' => now(),
            'picture' => 'guest-pictures/1/old-avatar.jpg',
        ]);
        Storage::disk('public')->put($guest->picture, 'old image');

        $this
            ->actingAs($guest, 'guard_guest')
            ->deleteJson('/api/guest/me/picture')
            ->assertOk()
            ->assertJsonPath('id', $guest->id)
            ->assertJsonPath('picture', null);

        $this->assertNull($guest->fresh()->picture);
        Storage::disk('public')->assertMissing('guest-pictures/1/old-avatar.jpg');
    }

    /** @test */
    public function staff_guest_delete_runs_gdpr_anonymization_flow()
    {
        $admin = Employee::factory()->create(['role_code' => Employee::ADMIN]);
        $guest = Guest::factory()->create([
            'email_verified_at' => now(),
            'email' => 'staff-delete@example.com',
            'phone' => '+36 30 123 4567',
        ]);

        $this
            ->actingAs($admin, 'guard_employee')
            ->deleteJson("/api/staff/guests/{$guest->id}")
            ->assertNoContent();

        $freshGuest = $guest->fresh();
        $this->assertSame("deleted-guest-{$guest->id}@anonymized.local", $freshGuest->email);
        $this->assertSame('staff_delete', $freshGuest->anonymization_reason);
        $this->assertNull($freshGuest->phone);
        $this->assertFalse($freshGuest->active);
        $this->assertNotNull($freshGuest->anonymized_at);
        $this->assertDatabaseHas('gdpr_audit_events', [
            'guest_id' => $guest->id,
            'actor_guest_id' => null,
            'event_type' => GdprAuditEvent::TYPE_ANONYMIZATION_COMPLETED,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function staff_guest_delete_is_blocked_by_gdpr_preconditions()
    {
        $admin = Employee::factory()->create(['role_code' => Employee::ADMIN]);
        $guest = Guest::factory()->create(['email_verified_at' => now()]);
        $order = Order::factory()->create([
            'guest_id' => $guest->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin, 'guard_employee')
            ->deleteJson("/api/staff/guests/{$guest->id}")
            ->assertStatus(409)
            ->assertJsonPath('can_anonymize', false)
            ->assertJsonPath('blocking_reasons.0.code', 'pending_payment');

        $this->assertNull($guest->fresh()->anonymized_at);
        $this->assertDatabaseHas('gdpr_audit_events', [
            'guest_id' => $guest->id,
            'actor_guest_id' => null,
            'event_type' => GdprAuditEvent::TYPE_ANONYMIZATION_BLOCKED,
            'status' => 'blocked',
        ]);
    }
}
