<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TableControllerTest extends TestCase
{
    use DatabaseMigrations;

    private string $password = 'Password1!';

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_staff_can_create_list_update_delete_and_regenerate_table_guid(): void
    {
        $staffToken = $this->staffToken();

        $createResponse = $this
            ->withToken($staffToken)
            ->postJson('/api/staff/tables', [
                'name' => 'Asztal 1',
                'active' => true,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('name', 'Asztal 1')
            ->assertJsonPath('active', true)
            ->assertJsonPath('status', 'available')
            ->assertJsonStructure(['id', 'guid']);

        $tableId = $createResponse->json('id');
        $oldGuid = $createResponse->json('guid');
        $this->assertNotEmpty($oldGuid);
        $this->assertDatabaseHas('tables', [
            'id' => $tableId,
            'name' => 'Asztal 1',
            'guid' => $oldGuid,
            'active' => true,
        ]);

        $this
            ->withToken($staffToken)
            ->getJson('/api/staff/tables')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Asztal 1']);

        $this
            ->withToken($staffToken)
            ->putJson("/api/staff/tables/{$tableId}", [
                'name' => 'Asztal 1A',
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Asztal 1A')
            ->assertJsonPath('active', false)
            ->assertJsonPath('status', 'inactive');

        $regenerateResponse = $this
            ->withToken($staffToken)
            ->postJson("/api/staff/tables/{$tableId}/regenerate-guid");

        $regenerateResponse->assertOk()
            ->assertJsonPath('id', $tableId);
        $this->assertNotSame($oldGuid, $regenerateResponse->json('guid'));

        $this
            ->withToken($staffToken)
            ->deleteJson("/api/staff/tables/{$tableId}")
            ->assertNoContent();

        $this->assertSoftDeleted('tables', ['id' => $tableId]);
    }

    public function test_guest_can_list_available_tables_claim_one_and_get_current_table(): void
    {
        $guestToken = $this->guestToken();
        $available = Table::factory()->create(['name' => 'Asztal 2']);
        TableSession::factory()->create([
            'table_id' => Table::factory()->create(['name' => 'Foglalt asztal'])->id,
            'owner_guest_id' => Guest::factory()->create()->id,
        ]);
        Table::factory()->create(['name' => 'Inaktív asztal', 'active' => false]);
        Table::factory()->create(['name' => 'Törölt asztal'])->delete();

        $availableResponse = $this
            ->withToken($guestToken)
            ->getJson('/api/guest/tables/available');

        $availableResponse->assertOk()
            ->assertJsonCount(1, 'tables')
            ->assertJsonPath('tables.0.id', $available->id)
            ->assertJsonPath('tables.0.name', 'Asztal 2')
            ->assertJsonPath('tables.0.guid', $available->guid)
            ->assertJsonPath('tables.0.status', 'available')
            ->assertJsonPath('tables.0.is_owner', false);

        $claimResponse = $this
            ->withToken($guestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $available->guid]);

        $claimResponse->assertOk()
            ->assertJsonPath('table.id', $available->id)
            ->assertJsonPath('table.name', 'Asztal 2')
            ->assertJsonPath('table.guid', $available->guid)
            ->assertJsonPath('table.status', 'reserved')
            ->assertJsonPath('table.is_owner', true)
            ->assertJsonPath('table_session.table_id', $available->id)
            ->assertJsonPath('table_session.owner_guest_id', Guest::where('email', 'guest-table@example.com')->value('id'))
            ->assertJsonPath('table_session.status', TableSession::STATUS_OPEN);

        $this->assertDatabaseHas('table_sessions', [
            'table_id' => $available->id,
            'owner_guest_id' => Guest::where('email', 'guest-table@example.com')->value('id'),
            'status' => TableSession::STATUS_OPEN,
            'closed_at' => null,
        ]);
        $this->assertNotNull(TableSession::where('table_id', $available->id)->first()->opened_at);

        $this
            ->withToken($guestToken)
            ->getJson('/api/guest/tables/current')
            ->assertOk()
            ->assertJsonPath('table.id', $available->id)
            ->assertJsonPath('table.is_owner', true)
            ->assertJsonPath('table_session.table_id', $available->id)
            ->assertJsonPath('table_session.status', TableSession::STATUS_OPEN);
    }

    public function test_current_table_returns_null_when_guest_has_no_table(): void
    {
        $this
            ->withToken($this->guestToken('guest-no-table@example.com'))
            ->getJson('/api/guest/tables/current')
            ->assertOk()
            ->assertJsonPath('table', null)
            ->assertJsonPath('table_session', null);
    }

    public function test_current_table_returns_approved_member_table(): void
    {
        $memberToken = $this->guestToken('guest-current-member@example.com');
        $memberId = Guest::where('email', 'guest-current-member@example.com')->value('id');
        $session = TableSession::factory()->create();
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $memberId,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->withToken($memberToken)
            ->getJson('/api/guest/tables/current')
            ->assertOk()
            ->assertJsonPath('table.id', $session->table_id)
            ->assertJsonPath('table.is_owner', false)
            ->assertJsonPath('table_session.id', $session->id)
            ->assertJsonPath('table_session.status', TableSession::STATUS_OPEN);
    }

    public function test_unauthenticated_table_requests_are_rejected(): void
    {
        $this->postJson('/api/staff/tables', [])->assertUnauthorized();
        $this->postJson('/api/guest/tables/claim', [])->assertUnauthorized();
    }

    public function test_staff_table_validation_pessimistic_cases(): void
    {
        $staffToken = $this->staffToken();

        $this
            ->withToken($staffToken)
            ->postJson('/api/staff/tables', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this
            ->withToken($staffToken)
            ->postJson('/api/staff/tables', ['active' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $table = Table::factory()->create();

        $this
            ->withToken($staffToken)
            ->putJson("/api/staff/tables/{$table->id}", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_guest_table_validation_pessimistic_cases(): void
    {
        $guestToken = $this->guestToken();

        $this
            ->withToken($guestToken)
            ->postJson('/api/guest/tables/claim', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guid']);

        $this
            ->withToken($guestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => 'not-a-guid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guid']);

        $this
            ->withToken($guestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => '00000000-0000-0000-0000-000000000000'])
            ->assertNotFound();
    }

    public function test_guest_token_cannot_access_staff_table_routes(): void
    {
        $this
            ->withToken($this->guestToken())
            ->getJson('/api/staff/tables')
            ->assertUnauthorized();
    }

    public function test_staff_token_cannot_access_guest_table_routes(): void
    {
        $this
            ->withToken($this->staffToken())
            ->getJson('/api/guest/tables/available')
            ->assertUnauthorized();
    }

    public function test_claim_rejects_inactive_reserved_soft_deleted_and_second_owned_table(): void
    {
        $firstGuestToken = $this->guestToken('guest-claim-one@example.com');
        $secondGuestToken = $this->guestToken('guest-claim-two@example.com');
        $reservedByOther = Guest::factory()->create([
            'email' => 'other-owner@example.com',
            'email_verified_at' => now(),
        ]);

        $inactive = Table::factory()->create(['active' => false]);
        $reserved = Table::factory()->create();
        TableSession::factory()->create([
            'table_id' => $reserved->id,
            'owner_guest_id' => $reservedByOther->id,
        ]);
        $deleted = Table::factory()->create();
        $deletedGuid = $deleted->guid;
        $deleted->delete();

        $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $inactive->guid])
            ->assertStatus(409);

        $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $reserved->guid])
            ->assertStatus(409);

        $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $deletedGuid])
            ->assertNotFound();

        $firstTable = Table::factory()->create();
        $secondTable = Table::factory()->create();

        $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $firstTable->guid])
            ->assertOk();

        $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $secondTable->guid])
            ->assertStatus(409);

        $this
            ->withToken($secondGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $firstTable->guid])
            ->assertStatus(409);
    }

    public function test_table_can_be_claimed_again_after_previous_session_is_closed_on_same_business_day(): void
    {
        $firstGuestToken = $this->guestToken('guest-repeat-one@example.com');
        $secondGuestToken = $this->guestToken('guest-repeat-two@example.com');
        $table = Table::factory()->create();

        $firstClaim = $this
            ->withToken($firstGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $table->guid]);

        $firstClaim->assertOk()
            ->assertJsonPath('table_session.table_id', $table->id)
            ->assertJsonPath('table_session.status', TableSession::STATUS_OPEN);

        $firstSession = TableSession::findOrFail($firstClaim->json('table_session.id'));
        $firstSession->close();

        Auth::forgetGuards();

        $secondClaim = $this
            ->withToken($secondGuestToken)
            ->postJson('/api/guest/tables/claim', ['guid' => $table->guid]);

        $secondClaim->assertOk()
            ->assertJsonPath('table.id', $table->id)
            ->assertJsonPath('table_session.table_id', $table->id)
            ->assertJsonPath('table_session.status', TableSession::STATUS_OPEN);

        $this->assertDatabaseCount('table_sessions', 2);
    }

    public function test_reserved_table_guid_cannot_be_regenerated(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $table = Table::factory()->create();
        TableSession::factory()->create([
            'table_id' => $table->id,
            'owner_guest_id' => $owner->id,
        ]);

        $this
            ->withToken($this->staffToken())
            ->postJson("/api/staff/tables/{$table->id}/regenerate-guid")
            ->assertStatus(409);
    }

    public function test_owner_can_update_current_table_spending_limit(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/tables/current/spending-limit', [
                'owner_spending_limit' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('table_session.id', $session->id)
            ->assertJsonPath('limits.owner_spending_limit', 2500)
            ->assertJsonPath('limits.effective_spending_limit', 2500);

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'owner_spending_limit' => 2500,
        ]);
    }

    public function test_non_owner_cannot_update_owner_spending_limit(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/current/spending-limit', [
                'owner_spending_limit' => 2500,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_override_staff_spending_limit_for_session(): void
    {
        config(['tables.default_staff_spending_limit' => 5000]);

        $admin = Employee::factory()->create(['role_code' => Employee::ADMIN]);
        $session = TableSession::factory()->create();

        $this
            ->actingAs($admin, 'guard_employee')
            ->postJson("/api/staff/table-sessions/{$session->id}/spending-limit", [
                'staff_spending_limit_override' => 3000,
            ])
            ->assertOk()
            ->assertJsonPath('table_session.id', $session->id)
            ->assertJsonPath('limits.default_staff_spending_limit', 5000)
            ->assertJsonPath('limits.staff_spending_limit_override', 3000)
            ->assertJsonPath('limits.effective_spending_limit', 3000);

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'staff_spending_limit_override' => 3000,
            'staff_spending_limit_override_set_by' => $admin->id,
        ]);
    }

    public function test_non_admin_staff_cannot_override_staff_spending_limit(): void
    {
        $waiter = Employee::factory()->create(['role_code' => Employee::WAITER]);
        $session = TableSession::factory()->create();

        $this
            ->actingAs($waiter, 'guard_employee')
            ->postJson("/api/staff/table-sessions/{$session->id}/spending-limit", [
                'staff_spending_limit_override' => 3000,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_view_current_table_stats(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
            'owner_spending_limit' => 5000,
        ]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $ownerOrder = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $ownerOrder->id,
            'ordered_quantity' => 2,
            'unit_price' => 600,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $ownerOrder->id,
            'ordered_quantity' => 1,
            'unit_price' => 400,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);

        $memberOrder = Order::factory()->create([
            'guest_id' => $member->id,
            'table_session_id' => $session->id,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $memberOrder->id,
            'ordered_quantity' => 3,
            'unit_price' => 500,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $response = $this
            ->actingAs($owner, 'guard_guest')
            ->getJson('/api/guest/tables/current/stats');

        $response->assertOk()
            ->assertJsonPath('table_session.id', $session->id)
            ->assertJsonPath('stats.payable_total', 2700)
            ->assertJsonPath('stats.effective_spending_limit', 5000)
            ->assertJsonPath('stats.remaining_spending_limit', 2300)
            ->assertJsonPath('stats.per_guest_consumption.0.guest_id', $owner->id)
            ->assertJsonPath('stats.per_guest_consumption.0.total', 1600)
            ->assertJsonPath('stats.per_guest_consumption.0.payable_total', 1200)
            ->assertJsonPath('stats.per_guest_consumption.0.paid_total', 400)
            ->assertJsonPath('stats.per_guest_consumption.1.guest_id', $member->id)
            ->assertJsonPath('stats.per_guest_consumption.1.total', 1500)
            ->assertJsonPath('stats.per_guest_consumption.1.payable_total', 1500)
            ->assertJsonPath('stats.per_guest_consumption.1.paid_total', 0);
    }

    public function test_current_table_stats_handles_detached_anonymized_orders(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create([
            'owner_guest_id' => $owner->id,
        ]);
        $order = Order::factory()->create([
            'guest_id' => null,
            'table_session_id' => $session->id,
            'status' => Order::STATUS_SERVED,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'ordered_quantity' => 1,
            'unit_price' => 700,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->getJson('/api/guest/tables/current/stats')
            ->assertOk()
            ->assertJsonPath('stats.per_guest_consumption.0.guest_id', null)
            ->assertJsonPath('stats.per_guest_consumption.0.name', __('Anonymized guest'))
            ->assertJsonPath('stats.per_guest_consumption.0.total', 700)
            ->assertJsonPath('stats.per_guest_consumption.0.paid_total', 700);
    }

    public function test_non_owner_cannot_view_current_table_stats(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($member, 'guard_guest')
            ->getJson('/api/guest/tables/current/stats')
            ->assertForbidden();
    }

    public function test_owner_can_close_fully_paid_current_table(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $table = Table::factory()->create();
        $session = TableSession::factory()->create([
            'table_id' => $table->id,
            'owner_guest_id' => $owner->id,
        ]);
        $order = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PAID,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/tables/current/close')
            ->assertOk()
            ->assertJsonPath('table.id', $table->id)
            ->assertJsonPath('table.status', 'available')
            ->assertJsonPath('table_session.id', $session->id)
            ->assertJsonPath('table_session.status', TableSession::STATUS_CLOSED);

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'status' => TableSession::STATUS_CLOSED,
        ]);
        $this->assertNotNull($session->fresh()->closed_at);
    }

    public function test_owner_cannot_close_current_table_with_pending_order_details(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        $order = Order::factory()->create([
            'guest_id' => $owner->id,
            'table_session_id' => $session->id,
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/tables/current/close')
            ->assertStatus(409);

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'status' => TableSession::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }

    public function test_non_owner_cannot_close_current_table(): void
    {
        $owner = Guest::factory()->create(['email_verified_at' => now()]);
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/current/close')
            ->assertForbidden();

        $this->assertDatabaseHas('table_sessions', [
            'id' => $session->id,
            'status' => TableSession::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }

    private function staffToken(string $email = 'staff-table@example.com'): string
    {
        Employee::factory()->create([
            'email' => $email,
            'password' => $this->password,
            'role_code' => Employee::ADMIN,
            'active' => true,
        ]);

        $response = $this->postJson('/api/staff/login', [
            'email' => $email,
            'password' => $this->password,
        ]);
        $response->assertOk();
        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        Auth::forgetGuards();

        return $token;
    }

    private function guestToken(string $email = 'guest-table@example.com'): string
    {
        Guest::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($this->password),
            'active' => true,
        ]);

        $response = $this->postJson('/api/guest/login', [
            'email' => $email,
            'password' => $this->password,
        ]);
        $response->assertOk();
        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        Auth::forgetGuards();

        return $token;
    }

}
