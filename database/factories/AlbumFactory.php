<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'description' => $this->faker->text(),
            'order' => $this->faker->randomDigit(),
            'date_start' => $this->faker->date(),
            'date_end' => $this->faker->date(),
            'published_at' => null,
            'location_id' => Location::factory(),
            'category_id' => Category::factory(),
        ];
    }

    public function published(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => $this->faker->dateTime(),
            ];
        });
    }

    public function archived(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'archived_at' => $this->faker->dateTime(),
            ];
        });
    }
}
