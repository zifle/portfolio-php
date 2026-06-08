<?php

namespace Database\Factories;

use App\Models\Camera;
use App\Models\Image;
use App\Models\Lens;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => $this->faker->filePath().'.jpeg',
            'available_res' => [400, 800, 1200, 2000],
            'max_width' => $this->faker->randomNumber(),
            'max_height' => $this->faker->randomNumber(),
            'description' => $this->faker->text(),
            'camera_id' => Camera::factory(),
            'lens_id' => Lens::factory(),
            'date_taken' => $this->faker->dateTime(),
            'focal_length' => $this->faker->randomNumber(),
            'focal_length_35' => $this->faker->randomNumber(),
            'exposure_time' => $this->faker->randomFloat(min: 1 / 1000, max: 1 / 30),
            'exposure_compensation' => $this->faker->randomFloat(min: -3, max: 3),
            'aperture' => $this->faker->randomNumber(),
        ];
    }
}
