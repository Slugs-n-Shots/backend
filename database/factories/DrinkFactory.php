<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drink>
 */
class DrinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cat = \App\Models\DrinkCategory::first();
        if ($cat === null) {
            \App\Models\DrinkCategory::factory()->create();
            $cat = \App\Models\DrinkCategory::first();
        }

        $suffix = fake()->unique()->numberBetween(100000, 999999);
        $name_en = fake('en_EN')->colorName() . ' ' . fake('en_EN')->dayOfWeek() . ' ' . $suffix;
        $name_hu = fake('hu_HU')->colorName() . ' ' . fake('hu_HU')->dayOfWeek() . ' ' . $suffix;
        return [
            'name_en' => $name_en,
            'name_hu' => $name_hu,
            'category_id' => $cat->id,
            'description_en' => "Description of {$name_en}",
            'description_hu' => "{$name_hu} leírása",
            'active' => 1
        ];
    }
}
