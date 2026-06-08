<?php

namespace Tests\Http\Controllers\Admin;

use App\Models\Image;
use App\Models\User;
use Faker\Core\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_single_image()
    {
        $user = User::factory()->create();
        $fileFaker = new File;
        // Faker uses /tmp/{filename} as path, we strip "/tmp/" to get just the filename
        $image_filename = substr($fileFaker->filePath(), 5).'.jpeg';
        $imgPath = Image::getImagePath($image_filename);
        $image = Image::factory()->create([
            'path' => $imgPath,
        ]);

        $date_taken = $image->date_taken->toISOString();

        $response = $this->actingAs($user)
            ->postJson(route('admin.images.check-duplicates'), [
                'images' => [
                    [
                        'filename' => $image_filename,
                        'date_taken' => $date_taken,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('images')
                ->where('images.0.id', $image->id)
                ->has('upload')
                ->where('upload', [])
                ->has('cameras')
                ->where('cameras.0.id', $image->camera_id)
                ->has('lenses')
                ->where('lenses.0.id', $image->lens_id)
                ->has('locations')
                ->where('locations', [])
            );
    }

    public function test_non_duplicate()
    {
        $user = User::factory()->create();
        $fileFaker = new File;
        // Faker uses /tmp/{filename} as path, we strip "/tmp/" to get just the filename
        $image_filename = substr($fileFaker->filePath(), 5).'.jpeg';
        $date_taken = now()->toISOString();

        $response = $this->actingAs($user)
            ->postJson(route('admin.images.check-duplicates'), [
                'images' => [
                    [
                        'filename' => $image_filename,
                        'date_taken' => $date_taken,
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('images')
                ->where('images', [])
                ->has('upload')
                ->where('upload', [$image_filename])
                ->has('cameras')
                ->where('cameras', [])
                ->has('lenses')
                ->where('lenses', [])
                ->has('locations')
                ->where('locations', [])
            );
    }

    public function test_duplicate_multiple()
    {
        $user = User::factory()->create();
        $fileFaker = new File;
        $images = [];
        $image_ids = [];
        for ($i = 0; $i < 3; $i++) {
            // Faker uses /tmp/{filename} as path, we strip "/tmp/" to get just the filename
            $image_filename = substr($fileFaker->filePath(), 5).'.jpeg';
            $imgPath = Image::getImagePath($image_filename);
            $image = Image::factory()->create([
                'path' => $imgPath,
            ]);

            $date_taken = $image->date_taken->toISOString();
            $images[] = ['filename' => $image_filename, 'date_taken' => $date_taken];
            $image_ids[] = $image->id;
        }

        $image_filename = substr($fileFaker->filePath(), 5).'.jpeg';
        $date_taken = now()->toISOString();
        $new_image = [
            'filename' => $image_filename,
            'date_taken' => $date_taken,
        ];
        $images[] = $new_image;

        $response = $this->actingAs($user)
            ->postJson(route('admin.images.check-duplicates'), [
                'images' => $images,
            ]);

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json->has('images')
                ->where('images.0.id', $image_ids[0])
                ->where('images.1.id', $image_ids[1])
                ->where('images.2.id', $image_ids[2])
                ->has('upload')
                ->where('upload', [$image_filename])
                ->has('cameras')
                ->has('lenses')
                ->has('locations')
                ->where('locations', [])
            );
    }
}
