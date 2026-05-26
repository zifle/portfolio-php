<?php

namespace Database\Factories;

use App\Models\TextBox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TextBox>
 */
class TextBoxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => $this->faker->text(),
            'col_size' => $this->faker->randomDigit(),
        ];
    }
}
