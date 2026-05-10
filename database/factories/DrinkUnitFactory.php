<?php

namespace Database\Factories;

use App\Models\Drink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DrinkUnit>
 */
class DrinkUnitFactory extends Factory
{
    private const UNITS = [
        ['unit_hu' => 'adag', 'unit_en' => 'doze'],
        ['unit_hu' => 'cl', 'unit_en' => 'cl'],
        ['unit_hu' => 'csésze', 'unit_en' => 'cup'],
        ['unit_hu' => 'dl', 'unit_en' => 'dl'],
        ['unit_hu' => 'l', 'unit_en' => 'l'],
        ['unit_hu' => 'pohár', 'unit_en' => 'glass'],
        ['unit_hu' => 'üveg', 'unit_en' => 'bottle'],
    ];

    public function definition(): array
    {
        $unit = fake()->randomElement(self::UNITS);
        return [
            'drink_id' => Drink::factory(),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'unit_en' => $unit['unit_en'],
            'unit_hu' => $unit['unit_hu'],
            'unit_price' => fake()->numberBetween(300, 3000),
            'active' => true,
        ];
    }
}
