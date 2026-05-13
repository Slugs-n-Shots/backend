<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TableMemberControllerTest extends TestCase
{
    use DatabaseMigrations;

    private string $password = 'Password1!';

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_guest_can_join_owner_can_approve_toggle_and_remove_member(): void
    {
        $owner = $this->guest('owner-table-member@example.com');
        $member = $this->guest('member-table-member@example.com');
        $table = Table::factory()->create(['name' => 'Asztal tagság']);
        $session = TableSession::factory()->create([
            'table_id' => $table->id,
            'owner_guest_id' => $owner->id,
        ]);

        $joinResponse = $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid]);

        $joinResponse->assertOk()
            ->assertJsonPath('membership.table_session_id', $session->id)
            ->assertJsonPath('membership.guest_id', $member->id)
            ->assertJsonPath('membership.status', TableMember::STATUS_PENDING)
            ->assertJsonPath('membership.can_order', true);

        $membershipId = $joinResponse->json('membership.id');

        $this
            ->actingAs($owner, 'guard_guest')
            ->getJson('/api/guest/tables/current/members')
            ->assertOk()
            ->assertJsonPath('members.0.guest_id', $owner->id)
            ->assertJsonPath('members.0.role', TableMember::ROLE_OWNER)
            ->assertJsonPath('pending.0.id', $membershipId)
            ->assertJsonPath('pending.0.guest_id', $member->id);

        $approveResponse = $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$membershipId}/approve");

        $approveResponse->assertOk()
            ->assertJsonPath('membership.status', TableMember::STATUS_APPROVED)
            ->assertJsonPath('membership.approved_by_guest_id', $owner->id)
            ->assertJsonPath('membership.can_order', true);

        $this
            ->actingAs($member, 'guard_guest')
            ->getJson('/api/guest/tables/current/members')
            ->assertOk()
            ->assertJsonCount(2, 'members')
            ->assertJsonPath('members.1.guest_id', $member->id)
            ->assertJsonPath('pending', []);

        $toggleResponse = $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$membershipId}/toggle-ordering", [
                'can_order' => false,
            ]);

        $toggleResponse->assertOk()
            ->assertJsonPath('membership.can_order', false);

        $removeResponse = $this
            ->actingAs($owner, 'guard_guest')
            ->deleteJson("/api/guest/tables/members/{$membershipId}");

        $removeResponse->assertOk()
            ->assertJsonPath('membership.status', TableMember::STATUS_REMOVED)
            ->assertJsonPath('membership.can_order', false);

        $this->assertNotNull(TableMember::find($membershipId)->removed_at);
    }

    public function test_owner_can_reject_pending_request_and_record_is_deleted(): void
    {
        $owner = $this->guest('owner-reject@example.com');
        $member = Guest::factory()->create(['email_verified_at' => now()]);
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        $membership = TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_PENDING,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$membership->id}/reject")
            ->assertNoContent();

        $this->assertDatabaseMissing('table_members', ['id' => $membership->id]);
    }

    public function test_join_pessimistic_cases(): void
    {
        $this->postJson('/api/guest/tables/join', [])->assertUnauthorized();

        $owner = $this->guest('owner-join-pessimistic@example.com');
        $member = $this->guest('member-join-pessimistic@example.com');
        $table = Table::factory()->create();
        $session = TableSession::factory()->create([
            'table_id' => $table->id,
            'owner_guest_id' => $owner->id,
        ]);

        $this
            ->withToken($this->staffToken())
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertUnauthorized();
        $this->resetAuthState();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guid']);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => 'not-a-guid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guid']);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => '00000000-0000-0000-0000-000000000000'])
            ->assertNotFound();

        $freeTable = Table::factory()->create();
        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $freeTable->guid])
            ->assertStatus(409);

        $inactiveTable = Table::factory()->create(['active' => false]);
        TableSession::factory()->create(['table_id' => $inactiveTable->id, 'owner_guest_id' => $owner->id]);
        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $inactiveTable->guid])
            ->assertStatus(409);

        $deletedTable = Table::factory()->create();
        $deletedGuid = $deletedTable->guid;
        $deletedTable->delete();
        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $deletedGuid])
            ->assertNotFound();

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertStatus(409);

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertOk();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertStatus(409);

        $membership = TableMember::where('table_session_id', $session->id)
            ->where('guest_id', $member->id)
            ->firstOrFail();

        $membership->status = TableMember::STATUS_APPROVED;
        $membership->save();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertStatus(409);

        $membership->status = TableMember::STATUS_DENIED;
        $membership->save();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertStatus(409);

        $membership->status = TableMember::STATUS_REMOVED;
        $membership->removed_at = now();
        $membership->save();

        $this
            ->actingAs($member, 'guard_guest')
            ->postJson('/api/guest/tables/join', ['guid' => $table->guid])
            ->assertOk()
            ->assertJsonPath('membership.status', TableMember::STATUS_PENDING);
    }

    public function test_only_owner_can_manage_members_and_status_transitions_are_valid(): void
    {
        $owner = $this->guest('owner-manage@example.com');
        $intruder = $this->guest('intruder-manage@example.com');
        $session = TableSession::factory()->create(['owner_guest_id' => $owner->id]);
        $pending = TableMember::factory()->create([
            'table_session_id' => $session->id,
            'status' => TableMember::STATUS_PENDING,
        ]);
        $approved = TableMember::factory()->create([
            'table_session_id' => $session->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($intruder, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$pending->id}/approve")
            ->assertForbidden();

        $this
            ->actingAs($intruder, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$pending->id}/reject")
            ->assertForbidden();

        $this
            ->actingAs($intruder, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$approved->id}/toggle-ordering", ['can_order' => false])
            ->assertForbidden();

        $this
            ->actingAs($intruder, 'guard_guest')
            ->deleteJson("/api/guest/tables/members/{$approved->id}")
            ->assertForbidden();

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$approved->id}/approve")
            ->assertStatus(409);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$approved->id}/reject")
            ->assertStatus(409);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$pending->id}/toggle-ordering", ['can_order' => false])
            ->assertStatus(409);

        $this
            ->actingAs($owner, 'guard_guest')
            ->postJson("/api/guest/tables/members/{$approved->id}/toggle-ordering", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['can_order']);

        $this
            ->actingAs($owner, 'guard_guest')
            ->deleteJson("/api/guest/tables/members/{$pending->id}")
            ->assertStatus(409);

        $ownerLikeMember = TableMember::factory()->create([
            'table_session_id' => $session->id,
            'role' => TableMember::ROLE_OWNER,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($owner, 'guard_guest')
            ->deleteJson("/api/guest/tables/members/{$ownerLikeMember->id}")
            ->assertStatus(409);
    }

    private function guest(string $email): Guest
    {
        return Guest::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($this->password),
            'active' => true,
        ]);
    }

    private function staffToken(string $email = 'staff-table-member@example.com'): string
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

        $this->resetAuthState();

        return $token;
    }

    private function resetAuthState(): void
    {
        Auth::forgetGuards();
        $this->flushHeaders();

        if (class_exists('JWTAuth')) {
            \JWTAuth::unsetToken();
        }
    }
}
