<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DrinkControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
        $this->employee = Employee::factory()->create();
    }

    public function test_index_returns_drinks_with_default_units_relation(): void
    {
        $this->authenticateStaff();
        $drink = $this->createDrinkWithUnit([
            'name_en' => 'Espresso',
            'name_hu' => 'Eszpresszo',
            'description_en' => 'Strong coffee.',
            'description_hu' => 'Eros kave.',
        ]);

        $response = $this->getJson('/api/staff/drinks?lang=en');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $drink->id)
            ->assertJsonPath('0.name', 'Espresso')
            ->assertJsonPath('0.description', 'Strong coffee.')
            ->assertJsonPath('0.units.0.unit_en', 'glass')
            ->assertJsonPath('0.units.0.unit_hu', 'pohar')
            ->assertJsonPath('0.units.0.unit', 'glass')
            ->assertJsonMissingPath('0.name_en')
            ->assertJsonMissingPath('0.description_en');
    }

    public function test_index_respects_with_and_nolang_query_parameters(): void
    {
        $this->authenticateStaff();
        $category = DrinkCategory::factory()->create([
            'name_en' => 'Coffee',
            'name_hu' => 'Kave',
        ]);
        $this->createDrinkWithUnit([
            'name_en' => 'Latte',
            'name_hu' => 'Tejeskave',
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/staff/drinks?with=category&nolang=1&lang=en');

        $response->assertOk()
            ->assertJsonPath('0.name_en', 'Latte')
            ->assertJsonPath('0.name_hu', 'Tejeskave')
            ->assertJsonPath('0.category.name', 'Coffee')
            ->assertJsonMissingPath('0.name')
            ->assertJsonPath('0.units.0.unit_en', 'glass');
    }

    public function test_store_validates_input_and_returns_created_drink_with_units(): void
    {
        $this->authenticateStaff();
        $category = DrinkCategory::factory()->create();

        $payload = [
            'name_en' => 'Tap Water',
            'name_hu' => 'Csapviz',
            'category_id' => $category->id,
            'description_en' => 'Fresh water.',
            'description_hu' => 'Friss viz.',
            'active' => true,
            'units' => [[
                'quantity' => 1,
                'unit_en' => 'glass',
                'unit_hu' => 'pohar',
                'unit_price' => 450,
            ]],
        ];

        $response = $this->postJson('/api/staff/drinks?lang=en', $payload);

        $response->assertOk()
            ->assertJsonPath('name', 'Tap Water')
            ->assertJsonPath('description', 'Fresh water.')
            ->assertJsonPath('category_id', $category->id)
            ->assertJsonPath('active', true)
            ->assertJsonPath('units.0.quantity', 1)
            ->assertJsonPath('units.0.unit_price', 450)
            ->assertJsonPath('units.0.unit', 'glass');

        $this->assertDatabaseHas('drinks', [
            'name_en' => 'Tap Water',
            'name_hu' => 'Csapviz',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('drink_units', [
            'unit_en' => 'glass',
            'unit_hu' => 'pohar',
            'unit_price' => 450,
        ]);
    }

    public function test_store_returns_validation_errors_for_invalid_payload(): void
    {
        $this->authenticateStaff();
        $category = DrinkCategory::factory()->create();

        $response = $this->postJson('/api/staff/drinks', [
            'name_en' => 'Broken Drink',
            'name_hu' => 'Hibas Ital',
            'category_id' => $category->id,
            'units' => [[
                'quantity' => 0,
                'unit_en' => 'glass',
                'unit_hu' => 'pohar',
                'unit_price' => -1,
            ]],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'units.0.quantity',
                'units.0.unit_price',
            ]);
        $this->assertDatabaseMissing('drinks', ['name_en' => 'Broken Drink']);
    }

    public function test_show_returns_requested_relations_and_localized_fields(): void
    {
        $this->authenticateStaff();
        $category = DrinkCategory::factory()->create(['name_en' => 'Soft Drinks']);
        $drink = $this->createDrinkWithUnit([
            'name_en' => 'Soda',
            'name_hu' => 'Szikviz',
            'category_id' => $category->id,
        ]);

        $response = $this->getJson("/api/staff/drinks/{$drink->id}?with=category,units&nolang=1&lang=en");

        $response->assertOk()
            ->assertJsonPath('id', $drink->id)
            ->assertJsonPath('name_en', 'Soda')
            ->assertJsonPath('name_hu', 'Szikviz')
            ->assertJsonPath('category.name', 'Soft Drinks')
            ->assertJsonPath('units.0.unit_en', 'glass')
            ->assertJsonMissingPath('name');
    }

    public function test_update_changes_scalar_fields_and_synchronizes_units(): void
    {
        $this->authenticateStaff();
        $drink = $this->createDrinkWithUnit(['name_en' => 'Old Name']);
        $keptUnit = $drink->units()->first();
        $deletedUnit = DrinkUnit::factory()->create([
            'drink_id' => $drink->id,
            'quantity' => 2,
            'unit_en' => 'bottle',
            'unit_hu' => 'uveg',
        ]);

        $response = $this->putJson("/api/staff/drinks/{$drink->id}?lang=en", [
            'name_en' => 'Updated Name',
            'active' => false,
            'units' => [
                [
                    'id' => $keptUnit->id,
                    'quantity' => 2,
                    'unit_en' => 'cup',
                    'unit_hu' => 'csésze',
                    'unit_price' => 900,
                ],
                [
                    'quantity' => 3,
                    'unit_en' => 'bottle',
                    'unit_hu' => 'uveg',
                    'unit_price' => 1800,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name')
            ->assertJsonPath('active', false)
            ->assertJsonCount(2, 'units');

        $this->assertDatabaseHas('drink_units', [
            'id' => $keptUnit->id,
            'quantity' => 2,
            'unit_en' => 'cup',
            'unit_price' => 900,
        ]);
        $this->assertDatabaseMissing('drink_units', ['id' => $deletedUnit->id]);
        $this->assertDatabaseHas('drink_units', [
            'drink_id' => $drink->id,
            'quantity' => 3,
            'unit_en' => 'bottle',
            'unit_price' => 1800,
        ]);
    }

    public function test_destroy_soft_deletes_drink_and_returns_no_content(): void
    {
        $this->authenticateStaff();
        $drink = $this->createDrinkWithUnit();

        $response = $this->deleteJson("/api/staff/drinks/{$drink->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('drinks', ['id' => $drink->id]);
    }

    public function test_scheme_returns_drink_table_shape(): void
    {
        $this->authenticateStaff();

        $response = $this->getJson('/api/staff/drinks/scheme');

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'name_en',
                'name_hu',
                'category_id',
                'description_en',
                'description_hu',
                'picture',
                'active',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);
    }

    public function test_guest_menu_and_card_return_public_active_drink_payloads(): void
    {
        $activeDrink = $this->createDrinkWithUnit([
            'name_en' => 'Lemonade',
            'name_hu' => 'Limonade',
            'active' => true,
        ]);
        $inactiveDrink = $this->createDrinkWithUnit([
            'name_en' => 'Hidden Drink',
            'name_hu' => 'Rejtett Ital',
            'active' => false,
        ]);

        $menuResponse = $this->getJson('/api/guest/menu?lang=en');

        $menuResponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $activeDrink->id)
            ->assertJsonPath('0.name', 'Lemonade')
            ->assertJsonPath('0.category_name', $activeDrink->category->name_en)
            ->assertJsonMissing(['id' => $inactiveDrink->id])
            ->assertJsonMissingPath('0.active')
            ->assertJsonMissingPath('0.category');

        $cardResponse = $this->getJson("/api/guest/drinks/card/{$activeDrink->id}?with=units&lang=en");

        $cardResponse->assertOk()
            ->assertJsonPath('id', $activeDrink->id)
            ->assertJsonPath('name', 'Lemonade')
            ->assertJsonPath('category_name', $activeDrink->category->name_en)
            ->assertJsonPath('units.0.unit', 'glass')
            ->assertJsonMissingPath('active')
            ->assertJsonMissingPath('category_id');
    }

    private function authenticateStaff(): void
    {
        parent::actingAs($this->employee, 'guard_employee');
        $this->withHeader('Authorization', 'Bearer ' . Auth::tokenById($this->employee->id));
    }

    private function createDrinkWithUnit(array $drinkAttributes = [], array $unitAttributes = []): Drink
    {
        $drink = Drink::factory()->create($drinkAttributes);
        DrinkUnit::factory()->create([
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_en' => 'glass',
            'unit_hu' => 'pohar',
            'unit_price' => 450,
            ...$unitAttributes,
        ]);

        return $drink->refresh();
    }
}
