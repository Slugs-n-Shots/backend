<?php

namespace Database\Factories;

use App\Helpers\StrUtils;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = rand(0, 1) ? 'male' : 'female';

        $last_name = fake()->lastName();
        $last_name .= rand(0, 4) ? '' : '-' . fake()->lastName();
        $first_name = fake()->firstName($gender);
        $middle_name = rand(0, 1) ? fake()->firstName($gender) : '';

        $name = implode(' ', array_filter([$last_name, $first_name, $middle_name]));
        $email = strtolower(str_replace(' ', '.', StrUtils::stripAccents($name))) . '@slugs-n-shots.hu';
        return [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => 'Password1!',
            'role_code' => rand(0, 2),
            'active' => 1,
        ];
    }
}
