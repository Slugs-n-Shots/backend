<?php

namespace Tests\Feature\Console;

use App\Models\Employee;
use App\Models\GdprAuditEvent;
use App\Models\Guest;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkTestDataGenerateTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_bulk_test_data_dry_run_does_not_write_data(): void
    {
        $guestCountBefore = Guest::count();

        $this
            ->artisan('test-data:bulk', [
                '--preset' => 'small',
                '--guests' => 5,
                '--orders' => 8,
                '--dry-run' => true,
                '--seed' => 123,
            ])
            ->assertSuccessful();

        $this->assertSame($guestCountBefore, Guest::count());
    }

    public function test_bulk_test_data_generates_lifelike_records_and_gdpr_cases(): void
    {
        $this
            ->artisan('test-data:bulk', [
                '--preset' => 'small',
                '--guests' => 8,
                '--employees' => 3,
                '--tables' => 4,
                '--orders' => 12,
                '--days' => 10,
                '--seed' => 123,
                '--force' => true,
            ])
            ->assertSuccessful();

        $this->assertGreaterThanOrEqual(8, Guest::count());
        $this->assertGreaterThanOrEqual(3, Employee::count());
        $this->assertGreaterThanOrEqual(4, Table::count());
        $this->assertGreaterThanOrEqual(12, Order::count());
        $this->assertDatabaseHas('guests', ['email' => 'gdpr.clean@example.com']);
        $this->assertDatabaseHas('guests', ['email' => 'gdpr.pending-payment@example.com']);
        $anonymizedGuest = Guest::where('anonymization_reason', 'guest_request')->first();
        $this->assertNotNull($anonymizedGuest);
        $this->assertSame("deleted-guest-{$anonymizedGuest->id}@anonymized.local", $anonymizedGuest->email);
        $this->assertDatabaseHas('gdpr_audit_events', [
            'event_type' => GdprAuditEvent::TYPE_ANONYMIZATION_COMPLETED,
            'status' => 'completed',
        ]);
    }
}
