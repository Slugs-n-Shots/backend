<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\DrinkUnit;
use App\Models\Guest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    public function test_authenticated_guest_can_place_three_item_order_and_view_active_status(): void
    {
        $guest = Guest::factory()->create([
            'email' => 'guest-order-flow@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password1!'),
        ]);

        $units = [
            $this->createDrinkUnit('Espresso', 1, 'cup', 650),
            $this->createDrinkUnit('Lemonade', 2, 'glass', 900),
            $this->createDrinkUnit('Mineral Water', 1.5, 'bottle', 700),
        ];

        $loginResponse = $this->postJson('/api/guest/login', [
            'email' => $guest->email,
            'password' => 'Password1!',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['access_token']);

        $cart = [
            [
                'drink_id' => $units[0]->drink_id,
                'quantity' => 1,
                'unit' => 'cup',
                'ordered_quantity' => 2,
            ],
            [
                'drink_id' => $units[1]->drink_id,
                'quantity' => 2,
                'unit' => 'glass',
                'ordered_quantity' => 1,
            ],
            [
                'drink_id' => $units[2]->drink_id,
                'quantity' => 1.5,
                'unit' => 'bottle',
                'ordered_quantity' => 3,
            ],
        ];

        $orderResponse = $this
            ->withToken($loginResponse->json('access_token'))
            ->postJson('/api/guest/orders?lang=en', ['cart' => $cart]);

        $orderId = $orderResponse->json('order.id');

        $orderResponse->assertOk()
            ->assertJsonPath('cart', $cart)
            ->assertJsonPath('order.guest_id', $guest->id)
            ->assertJsonPath('order.status', 'in progress')
            ->assertJsonCount(3, 'order.details')
            ->assertJsonPath('order.details.0.drink_unit.drink.name', 'Espresso')
            ->assertJsonPath('order.details.1.drink_unit.drink.name', 'Lemonade')
            ->assertJsonPath('order.details.2.drink_unit.drink.name', 'Mineral Water')
            ->assertJsonPath('total', 4300);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'guest_id' => $guest->id,
            'served_at' => null,
        ]);

        foreach ($units as $idx => $unit) {
            $this->assertDatabaseHas('order_details', [
                'order_id' => $orderId,
                'drink_unit_id' => $unit->id,
                'ordered_quantity' => $cart[$idx]['ordered_quantity'],
                'unit_price' => $unit->unit_price,
                'discount' => 0,
            ]);
        }

        $statusResponse = $this
            ->withToken($loginResponse->json('access_token'))
            ->getJson('/api/guest/orders/active?lang=en');

        $statusResponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $orderId)
            ->assertJsonPath('0.guest_id', $guest->id)
            ->assertJsonPath('0.status', 'in progress')
            ->assertJsonCount(3, '0.details')
            ->assertJsonPath('0.details.0.drink_unit.drink.name', 'Espresso')
            ->assertJsonPath('0.details.1.drink_unit.drink.name', 'Lemonade')
            ->assertJsonPath('0.details.2.drink_unit.drink.name', 'Mineral Water');
    }

    private function createDrinkUnit(string $drinkName, float $quantity, string $unit, int $price): DrinkUnit
    {
        $category = DrinkCategory::factory()->create();
        $drink = Drink::factory()->create([
            'category_id' => $category->id,
            'name_en' => $drinkName,
            'name_hu' => $drinkName,
            'active' => true,
        ]);

        return DrinkUnit::factory()->create([
            'drink_id' => $drink->id,
            'quantity' => $quantity,
            'unit_en' => $unit,
            'unit_hu' => $unit,
            'unit_price' => $price,
            'active' => true,
        ]);
    }
}
