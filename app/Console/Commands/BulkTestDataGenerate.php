<?php

namespace App\Console\Commands;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use App\Models\Employee;
use App\Models\GdprAuditEvent;
use App\Models\Guest;
use App\Models\GuestRecentDrink;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\Table;
use App\Models\TableMember;
use App\Models\TableSession;
use App\Services\GuestAnonymizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BulkTestDataGenerate extends Command
{
    protected $signature = 'test-data:bulk
        {--preset=demo : small, demo or load}
        {--fresh : Run migrate:fresh before generating data}
        {--force : Skip confirmation prompts}
        {--dry-run : Print the planned counts without writing data}
        {--seed= : Deterministic random seed}
        {--guests= : Override guest count}
        {--employees= : Override employee count}
        {--tables= : Override table count}
        {--orders= : Override order count}
        {--days= : Override generated history window}
        {--with-gdpr-cases : Generate named GDPR scenario guests}';

    protected $description = 'Generate bulk, lifelike local/test data for demo, QA and load scenarios.';

    private array $drinkUnits = [];
    private array $employees = [];
    private array $guests = [];
    private array $tables = [];
    private array $openSessions = [];

    public function handle(): int
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->error('Bulk test data generation is only allowed in local or testing environments.');

            return self::FAILURE;
        }

        $options = $this->optionsFromPreset();
        $seed = $this->option('seed') !== null ? (int) $this->option('seed') : random_int(1, PHP_INT_MAX);
        fake()->seed($seed);
        mt_srand($seed);

        $this->info('Bulk test data generation');
        $this->line('Preset: ' . $this->option('preset'));
        $this->line('Seed: ' . $seed);
        $this->line('Guests: ' . $options['guests']);
        $this->line('Employees: ' . $options['employees']);
        $this->line('Tables: ' . $options['tables']);
        $this->line('Orders: ' . $options['orders']);
        $this->line('Days: ' . $options['days']);
        $this->line('GDPR cases: ' . ($options['with_gdpr_cases'] ? 'yes' : 'no'));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            if (!$this->option('force') && !$this->confirm('This will run migrate:fresh and delete current data. Continue?')) {
                return self::FAILURE;
            }

            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->output->write(Artisan::output());
        }

        DB::transaction(function () use ($options) {
            $this->employees = $this->generateEmployees($options['employees']);
            $this->drinkUnits = $this->ensureCatalog();
            $this->tables = $this->generateTables($options['tables']);
            $this->guests = $this->generateGuests($options['guests']);
            $this->openSessions = $this->generateTableScenarios();
            $this->generateOrders($options['orders'], $options['days']);

            if ($options['with_gdpr_cases']) {
                $this->generateGdprCases($options['days']);
            }
        });

        $this->info('Bulk test data generation completed.');

        return self::SUCCESS;
    }

    private function optionsFromPreset(): array
    {
        $preset = (string) $this->option('preset');
        $presets = config('test_data.presets', []);

        if (!array_key_exists($preset, $presets)) {
            $this->fail("Unknown preset [{$preset}].");
        }

        $options = $presets[$preset];

        foreach (['guests', 'employees', 'tables', 'orders', 'days'] as $key) {
            if ($this->option($key) !== null) {
                $options[$key] = max((int) $this->option($key), 0);
            }
        }

        $options['guests'] = max((int) $options['guests'], 1);
        $options['employees'] = max((int) $options['employees'], 1);
        $options['tables'] = max((int) $options['tables'], 2);
        $options['orders'] = max((int) $options['orders'], 0);
        $options['days'] = max((int) $options['days'], 1);
        $options['with_gdpr_cases'] = (bool) ($this->option('with-gdpr-cases') || $options['with_gdpr_cases']);

        return $options;
    }

    private function generateEmployees(int $count): array
    {
        $fixed = [
            ['first_name' => 'Bulk', 'last_name' => 'Admin', 'email' => 'bulk.admin@slugs-n-shots.test', 'role_code' => Employee::ADMIN],
            ['first_name' => 'Bulk', 'last_name' => 'Bartender', 'email' => 'bulk.bartender@slugs-n-shots.test', 'role_code' => Employee::BARTENDER],
            ['first_name' => 'Bulk', 'last_name' => 'Waiter', 'email' => 'bulk.waiter@slugs-n-shots.test', 'role_code' => Employee::WAITER],
        ];

        $employees = [];
        foreach (array_slice($fixed, 0, max($count, 1)) as $employee) {
            $employees[] = Employee::firstOrCreate(
                ['email' => $employee['email']],
                $employee + ['middle_name' => null, 'password' => 'Password1!', 'active' => true]
            );
        }

        while (count($employees) < $count) {
            $employees[] = Employee::factory()->create([
                'password' => 'Password1!',
                'role_code' => fake()->randomElement([Employee::WAITER, Employee::BARTENDER, Employee::BACKOFFICE]),
                'active' => true,
            ]);
        }

        return $employees;
    }

    private function ensureCatalog(): array
    {
        if (DrinkUnit::query()->where('active', true)->count() >= 12) {
            return DrinkUnit::query()->where('active', true)->with('drink')->get()->all();
        }

        $categoryNames = [
            ['name_en' => 'Coffee', 'name_hu' => 'Coffee'],
            ['name_en' => 'Beer', 'name_hu' => 'Beer'],
            ['name_en' => 'Cocktails', 'name_hu' => 'Cocktails'],
            ['name_en' => 'Soft drinks', 'name_hu' => 'Soft drinks'],
        ];

        foreach ($categoryNames as $categoryName) {
            $category = DrinkCategory::firstOrCreate(['name_en' => $categoryName['name_en']], $categoryName);

            for ($i = 1; $i <= 4; $i++) {
                $drink = Drink::firstOrCreate(
                    ['name_en' => "{$categoryName['name_en']} {$i}"],
                    [
                        'name_hu' => "{$categoryName['name_hu']} {$i}",
                        'category_id' => $category->id,
                        'description_en' => "Bulk generated {$categoryName['name_en']} drink.",
                        'description_hu' => "Bulk generated {$categoryName['name_hu']} drink.",
                        'active' => true,
                    ]
                );

                DrinkUnit::firstOrCreate(
                    ['drink_id' => $drink->id, 'quantity' => 1, 'unit_en' => 'glass'],
                    ['unit_hu' => 'glass', 'unit_price' => fake()->numberBetween(500, 2500), 'active' => true]
                );
            }
        }

        return DrinkUnit::query()->where('active', true)->with('drink')->get()->all();
    }

    private function generateTables(int $count): array
    {
        $tables = [];
        for ($i = 1; $i <= $count; $i++) {
            $tables[] = Table::factory()->create(['name' => "Bulk table {$i}", 'active' => true]);
        }

        return $tables;
    }

    private function generateGuests(int $count): array
    {
        $guests = [];
        for ($i = 1; $i <= $count; $i++) {
            $guests[] = Guest::factory()->create([
                'email_verified_at' => now(),
                'is_over_18' => true,
                'age_verified_at' => now()->subDays(fake()->numberBetween(1, 120)),
                'birth_date' => now()->subYears(fake()->numberBetween(18, 65))->subDays(fake()->numberBetween(0, 364))->toDateString(),
                'phone' => fake()->optional(0.45)->phoneNumber(),
                'address' => fake()->optional(0.35)->address(),
                'active' => true,
            ]);
        }

        return $guests;
    }

    private function generateTableScenarios(): array
    {
        $sessions = [];
        $tables = array_slice($this->tables, 0, min(5, count($this->tables)));

        foreach ($tables as $idx => $table) {
            $owner = $this->randomGuest();
            $session = TableSession::factory()->create([
                'table_id' => $table->id,
                'owner_guest_id' => $owner->id,
                'owner_spending_limit' => $idx % 2 === 0 ? 20000 : null,
                'owner_per_guest_spending_limit' => $idx % 2 === 0 ? 6000 : null,
            ]);
            $sessions[] = $session;
            $usedGuestIds = [$owner->id];

            foreach (range(1, fake()->numberBetween(1, 3)) as $memberIdx) {
                $member = $this->randomGuest($usedGuestIds);
                $usedGuestIds[] = $member->id;
                TableMember::factory()->create([
                    'table_session_id' => $session->id,
                    'guest_id' => $member->id,
                    'status' => $memberIdx === 1 ? TableMember::STATUS_PENDING : TableMember::STATUS_APPROVED,
                    'can_order' => $memberIdx !== 3,
                    'approved_by_guest_id' => $memberIdx === 1 ? null : $owner->id,
                    'approved_at' => $memberIdx === 1 ? null : now()->subMinutes(fake()->numberBetween(5, 120)),
                ]);
            }
        }

        return $sessions;
    }

    private function generateOrders(int $count, int $days): void
    {
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $guest = $this->randomGuest();
            $recordedAt = now()
                ->subDays(fake()->numberBetween(0, max($days, 1)))
                ->setTime(fake()->numberBetween(16, 23), fake()->numberBetween(0, 59));
            $status = fake()->randomElement([
                Order::STATUS_SERVED,
                Order::STATUS_SERVED,
                Order::STATUS_SERVED,
                Order::STATUS_OPEN,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
                Order::STATUS_CANCELLED,
            ]);
            $tableSession = fake()->optional(0.35)->randomElement($this->openSessions);

            $order = Order::factory()->create([
                'guest_id' => $guest->id,
                'recorded_at' => $recordedAt,
                'status' => $status,
                'table_session_id' => $tableSession?->id,
                'table' => $tableSession?->table?->name,
            ]);

            $paid = in_array($status, [Order::STATUS_SERVED, Order::STATUS_CANCELLED], true) && fake()->boolean(75);
            $receipt = null;
            $paymentAttempt = null;

            if ($paid) {
                $paymentAttempt = PaymentAttempt::factory()->create([
                    'guest_id' => $guest->id,
                    'table_session_id' => $tableSession?->id,
                    'status' => PaymentAttempt::STATUS_SUCCEEDED,
                    'payment_method' => fake()->randomElement([PaymentAttempt::METHOD_CASH, PaymentAttempt::METHOD_CARD]),
                    'started_at' => $recordedAt->copy()->addMinutes(20),
                    'finished_at' => $recordedAt->copy()->addMinutes(25),
                ]);
                $receipt = Receipt::factory()->create([
                    'guest_id' => $guest->id,
                    'table_session_id' => $tableSession?->id,
                    'payment_attempt_id' => $paymentAttempt->id,
                    'issued_at' => $recordedAt->copy()->addMinutes(25),
                    'paid_at' => $recordedAt->copy()->addMinutes(25),
                    'paid_for' => $this->randomEmployee()->id,
                    'payment_method' => $paymentAttempt->payment_method,
                    'table' => $tableSession?->table?->name,
                ]);
                $paymentAttempt->receipt_id = $receipt->id;
                $paymentAttempt->save();
            }

            $total = 0;
            foreach (range(1, fake()->numberBetween(1, 4)) as $unused) {
                $unit = $this->randomDrinkUnit();
                $quantity = fake()->numberBetween(1, 3);
                $lineTotal = $unit->unit_price * $quantity;
                $total += $lineTotal;

                $detail = OrderDetail::factory()->create([
                    'order_id' => $order->id,
                    'drink_unit_id' => $unit->id,
                    'ordered_quantity' => $quantity,
                    'unit_price' => $unit->unit_price,
                    'receipt_id' => $receipt?->id,
                    'payment_status' => $paid ? OrderDetail::PAYMENT_STATUS_PAID : OrderDetail::PAYMENT_STATUS_PENDING,
                ]);

                if ($paid) {
                    PaymentEvent::factory()->create([
                        'payment_attempt_id' => $paymentAttempt->id,
                        'event_type' => PaymentEvent::TYPE_PAYMENT_SUCCEEDED,
                        'actor_guest_id' => $guest->id,
                        'order_detail_id' => $detail->id,
                        'receipt_id' => $receipt->id,
                    ]);
                }
            }

            if ($paymentAttempt !== null) {
                $paymentAttempt->amount = $total;
                $paymentAttempt->save();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function generateGdprCases(int $days): void
    {
        $clean = $this->scenarioGuest('gdpr.clean@example.com');
        $this->scenarioGuest('gdpr.retention-old@example.com');
        $this->scenarioGuest('gdpr.pending-payment@example.com');
        $this->scenarioGuest('gdpr.open-table-owner@example.com');
        $this->scenarioGuest('gdpr.open-member@example.com');

        $order = Order::factory()->create([
            'guest_id' => Guest::where('email', 'gdpr.pending-payment@example.com')->value('id'),
            'status' => Order::STATUS_SERVED,
            'recorded_at' => now()->subDays(max($days, 2)),
        ]);
        OrderDetail::factory()->create([
            'order_id' => $order->id,
            'drink_unit_id' => $this->randomDrinkUnit()->id,
            'payment_status' => OrderDetail::PAYMENT_STATUS_PENDING,
        ]);

        $owner = Guest::where('email', 'gdpr.open-table-owner@example.com')->firstOrFail();
        TableSession::factory()->create([
            'table_id' => $this->tables[0]->id,
            'owner_guest_id' => $owner->id,
            'status' => TableSession::STATUS_OPEN,
        ]);

        $member = Guest::where('email', 'gdpr.open-member@example.com')->firstOrFail();
        $session = $this->openSessions[0] ?? TableSession::factory()->create([
            'table_id' => $this->tables[1]->id,
            'owner_guest_id' => $clean->id,
        ]);
        TableMember::factory()->create([
            'table_session_id' => $session->id,
            'guest_id' => $member->id,
            'status' => TableMember::STATUS_APPROVED,
        ]);

        $anonymized = $this->scenarioGuest('gdpr.anonymized@example.com');
        app(GuestAnonymizationService::class)->anonymize($anonymized);

        GuestRecentDrink::updateOrCreate(
            ['guest_id' => $clean->id, 'drink_id' => $this->randomDrinkUnit()->drink_id],
            ['last_ordered_at' => now()->subDay(), 'order_count' => 3]
        );
    }

    private function scenarioGuest(string $email): Guest
    {
        return Guest::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'GDPR',
                'middle_name' => null,
                'last_name' => str($email)->before('@')->after('gdpr.')->headline()->toString(),
                'password' => Hash::make('Password1!'),
                'active' => true,
                'email_verified_at' => now(),
                'is_over_18' => true,
                'age_verified_at' => now(),
                'birth_date' => '1990-01-02',
                'phone' => '+36 30 123 4567',
                'address' => '1117 Test Street 1.',
            ]
        );
    }

    private function randomGuest(array $excludeIds = []): Guest
    {
        $pool = array_values(array_filter($this->guests, fn (Guest $guest) => !in_array($guest->id, $excludeIds, true)));

        if ($pool === []) {
            $guest = Guest::factory()->create(['email_verified_at' => now(), 'is_over_18' => true, 'age_verified_at' => now()]);
            $this->guests[] = $guest;

            return $guest;
        }

        return $pool[array_rand($pool)];
    }

    private function randomEmployee(): Employee
    {
        return $this->employees[array_rand($this->employees)];
    }

    private function randomDrinkUnit(): DrinkUnit
    {
        return $this->drinkUnits[array_rand($this->drinkUnits)];
    }
}
