<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Kami\Cocktail\Models\User;
use Tests\TestCase;

class ImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_single_image_upload()
    {
        Storage::fake('app_images');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->image('image.jpg'),
                    'copyright' => 'Made with test',
                ]
            ],
        ]);

        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', 1, function ($json) {
                $json->has('id')
                    ->has('file_path')
                    ->where('copyright', 'Made with test')
                    ->etc();
            });
        });

        $filename = $response->json('data.0.file_path');

        Storage::disk('app_images')->assertExists($filename);
    }

    public function test_multiple_image_upload()
    {
        Storage::fake('app_images');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->image('image1.jpg'),
                    'copyright' => 'BA 1',
                ],
                [
                    'image' => UploadedFile::fake()->image('image2.jpg'),
                    'copyright' => 'BA 2',
                ],
                [
                    'image' => UploadedFile::fake()->image('image3.jpg'),
                    'copyright' => 'BA 3',
                ]
            ],
        ]);

        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', 3, function ($json) {
                $json->has('id')
                    ->has('file_path')
                    ->has('copyright')
                    ->etc();
            });
        });

        Storage::disk('app_images')->assertExists($response->json('data.0.file_path'));
        Storage::disk('app_images')->assertExists($response->json('data.1.file_path'));
        Storage::disk('app_images')->assertExists($response->json('data.2.file_path'));
    }
}
