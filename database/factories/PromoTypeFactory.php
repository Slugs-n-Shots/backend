<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoType>
 */
class PromoTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description_en' => fake('en_US')->sentence(3),
            'description_hu' => fake('hu_HU')->sentence(3),
        ];
    }
}
