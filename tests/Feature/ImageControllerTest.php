<?php

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        Storage::fake('bar-assistant');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->image('image.jpg'),
                    'copyright' => 'Made with test',
                    'sort' => 1,
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

        Storage::disk('bar-assistant')->assertExists($filename);
    }

    public function test_multiple_image_upload()
    {
        Storage::fake('bar-assistant');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->image('image1.jpg'),
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ],
                [
                    'image' => UploadedFile::fake()->image('image2.jpg'),
                    'copyright' => 'BA 2',
                    'sort' => 1,
                ],
                [
                    'image' => UploadedFile::fake()->image('image3.jpg'),
                    'copyright' => 'BA 3',
                    'sort' => 1,
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

        Storage::disk('bar-assistant')->assertExists($response->json('data.0.file_path'));
        Storage::disk('bar-assistant')->assertExists($response->json('data.1.file_path'));
        Storage::disk('bar-assistant')->assertExists($response->json('data.2.file_path'));
    }

    public function test_image_thumb()
    {
        Storage::fake('bar-assistant');
        $imageFile = UploadedFile::fake()->image('image1.jpg');

        $cocktailImage = Image::factory()->for(Cocktail::factory(), 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'bar-assistant'),
            'file_extension' => $imageFile->extension(),
            'user_id' => auth()->user()->id
        ]);

        $response = $this->get('/api/images/' . $cocktailImage->id . '/thumb');

        $response->assertOk();
    }
}
