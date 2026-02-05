<?php

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\File;
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
        $s = Storage::fake('uploads');
        File::ensureDirectoryExists($s->path('temp'));

        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->createWithContent('image.jpg', $this->getFakeImageContent('jpg')),
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

        Storage::disk('uploads')->assertExists($filename);
    }

    public function test_single_image_upload_updates_old_image(): void
    {
        $s = Storage::fake('uploads');
        File::ensureDirectoryExists($s->path('temp'));

        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->createWithContent('image.jpg', $this->getFakeImageContent('jpg')),
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

        Storage::disk('uploads')->assertExists($filename);

        $oldId = $response->json('data.0.id');
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'id' => $oldId,
                    'image' => UploadedFile::fake()->createWithContent('image.jpg', $this->getFakeImageContent('jpg')),
                    'copyright' => 'New image',
                    'sort' => 2,
                ]
            ],
        ]);
        $newFilename = $response->json('data.0.file_path');

        Storage::disk('uploads')->assertExists($newFilename);
        Storage::disk('uploads')->assertMissing($filename);
    }

    public function test_multiple_image_upload(): void
    {
        $s = Storage::fake('uploads');
        File::ensureDirectoryExists($s->path('temp'));

        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg')),
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ],
                [
                    'image' => UploadedFile::fake()->createWithContent('image2.jpg', $this->getFakeImageContent('jpg')),
                    'copyright' => 'BA 2',
                    'sort' => 1,
                ],
                [
                    'image' => UploadedFile::fake()->createWithContent('image3.jpg', $this->getFakeImageContent('jpg')),
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

        Storage::disk('uploads')->assertExists($response->json('data.0.file_path'));
        Storage::disk('uploads')->assertExists($response->json('data.1.file_path'));
        Storage::disk('uploads')->assertExists($response->json('data.2.file_path'));
    }

    public function test_multiple_image_upload_fails(): void
    {
        Storage::fake('uploads');
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
        $s = Storage::fake('uploads');
        File::ensureDirectoryExists($s->path('temp'));

        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => 'https://raw.githubusercontent.com/karlomikus/bar-assistant/master/resources/art/readme-header.png',
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

        $filename = $response->json('data.0.file_path');

        Storage::disk('uploads')->assertExists($filename);
    }

    public function test_multiple_image_upload_by_url_fails(): void
    {
        $response = $this->post('/api/images', [
            'images' => [
                [
                    'image' => 'test',
                    'copyright' => 'BA 1',
                    'sort' => 1,
                ],
                [
                    'image' => file_get_contents(base_path('tests/fixtures/sus_image.txt')),
                    'copyright' => 'BA 2',
                    'sort' => 2,
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable();
    }

    public function test_image_thumb(): void
    {
        Storage::fake('uploads');
        $imageFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));

        $cocktailImage = Image::factory()->for(Cocktail::factory(), 'imageable')->create([
            'file_path' => $imageFile->storeAs('temp', 'image1.jpg', 'uploads'),
            'file_extension' => $imageFile->extension(),
            'created_user_id' => auth('sanctum')->user()->id
        ]);

        $response = $this->get('/api/images/' . $cocktailImage->id . '/thumb');

        $response->assertOk();
    }
}
