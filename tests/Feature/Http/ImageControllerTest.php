<?php

namespace Tests\Feature\Http;

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

    public function test_single_image_upload(): void
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

    public function test_multiple_image_upload(): void
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

    public function test_multiple_image_upload_fails(): void
    {
        Storage::fake('bar-assistant');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->create('image1.doc'),
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ]
            ],
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable();
    }

    public function test_multiple_image_upload_by_url(): void
    {
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image_url' => 'https://raw.githubusercontent.com/karlomikus/bar-assistant/master/resources/art/readme-header.png',
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ],
            ],
        ]);

        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', 1, function ($json) {
                $json->has('id')
                    ->has('file_path')
                    ->where('copyright', 'BA 1')
                    ->where('sort', 1)
                    ->etc();
            });
        });
    }

    public function test_multiple_image_upload_by_url_fails(): void
    {
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image_url' => 'test',
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable();
    }

    public function test_image_thumb(): void
    {
        Storage::fake('bar-assistant');
        $imageFile = UploadedFile::fake()->image('image1.jpg');

        $cocktailImage = Image::factory()->for(Cocktail::factory(), 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'bar-assistant'),
            'file_extension' => $imageFile->extension(),
            'created_user_id' => auth()->user()->id
        ]);

        $response = $this->get('/api/images/' . $cocktailImage->id . '/thumb');

        $response->assertOk();
    }

    public function test_image_update(): void
    {
        $bar = $this->setupBar();
        Storage::fake('bar-assistant');
        $imageFile = UploadedFile::fake()->image('image1.jpg');
        $cocktailImage = Image::factory()->for(Cocktail::factory()->create(['bar_id' => $bar->id]), 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'bar-assistant'),
            'file_extension' => $imageFile->extension(),
            'copyright' => 'initial',
            'sort' => 7,
            'created_user_id' => auth()->user()->id
        ]);

        $response = $this->postJson('/api/images/' . $cocktailImage->id, [
            'copyright' => 'New copyright'
        ]);
        $response->assertJsonPath('data.id', $cocktailImage->id);
        $response->assertJsonPath('data.file_path', 'temp/image1.jpg');
        $response->assertJsonPath('data.copyright', 'New copyright');
        $response->assertJsonPath('data.sort', 7);

        $response = $this->postJson('/api/images/' . $cocktailImage->id, [
            'sort' => 1
        ]);
        $response->assertJsonPath('data.id', $cocktailImage->id);
        $response->assertJsonPath('data.file_path', 'temp/image1.jpg');
        $response->assertJsonPath('data.copyright', 'New copyright');
        $response->assertJsonPath('data.sort', 1);

        $response = $this->postJson('/api/images/' . $cocktailImage->id, [
            'image' => UploadedFile::fake()->image('new_image.png')
        ]);
        $response->assertJsonPath('data.id', $cocktailImage->id);
        $this->assertNotSame('temp/image1.jpg', $response->json('data.file_path'));
        $response->assertJsonPath('data.copyright', 'New copyright');
        $response->assertJsonPath('data.sort', 1);
    }

    public function test_image_update_fails(): void
    {
        Storage::fake('bar-assistant');
        $imageFile = UploadedFile::fake()->image('image1.jpg');
        $cocktailImage = Image::factory()->for(Cocktail::factory(), 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'bar-assistant'),
            'file_extension' => $imageFile->extension(),
            'copyright' => 'initial',
            'sort' => 7,
            'created_user_id' => auth()->user()->id
        ]);

        $response = $this->post('/api/images/' . $cocktailImage->id, [
            'image' => UploadedFile::fake()->create('new_image.doc')
        ], ['Accept' => 'application/json']);
        $response->assertUnprocessable();

        $response = $this->post('/api/images/' . $cocktailImage->id, [
            'image_url' => 'not-url'
        ], ['Accept' => 'application/json']);
        $response->assertUnprocessable();

        $response = $this->post('/api/images/' . $cocktailImage->id, [
            'sort' => 'one'
        ], ['Accept' => 'application/json']);
        $response->assertUnprocessable();
    }
}
