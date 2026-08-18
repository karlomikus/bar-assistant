<?php

declare(strict_types=1);

namespace Tests\Unit\External;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\External\Model\Glass as GlassExternal;
use Kami\Cocktail\External\Model\Image as ImageExternal;

class GlassExternalTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_model_with_images_serializes_to_datapack_array(): void
    {
        Storage::fake('uploads');
        $bar = Bar::factory()->create();
        $glass = Glass::factory()->for($bar)->create([
            'name' => 'Highball',
            'description' => 'A tall glass',
            'volume' => 350.0,
            'volume_units' => 'ml',
        ]);

        $file = UploadedFile::fake()->createWithContent('g-1.jpg', $this->getFakeImageContent('jpg'));
        Image::factory()->for($glass, 'imageable')->create([
            'file_path' => $file->storeAs('glasses/' . $bar->id, 'g-1.jpg', 'uploads'),
            'file_extension' => 'jpg',
            'copyright' => 'Photographer',
            'sort' => 1,
            'placeholder_hash' => 'abc123',
        ]);

        $external = GlassExternal::fromModel($glass->load('images'), useFileURI: true);

        $data = $external->toDataPackArray();

        $this->assertSame($glass->getExternalId(), $data['_id']);
        $this->assertSame('Highball', $data['name']);
        $this->assertSame('A tall glass', $data['description']);
        $this->assertSame(350.0, $data['volume']);
        $this->assertSame('ml', $data['volume_units']);
        $this->assertCount(1, $data['images']);
        $this->assertSame('file:///g-1.jpg', $data['images'][0]['uri']);
        $this->assertSame('Photographer', $data['images'][0]['copyright']);
        $this->assertSame(1, $data['images'][0]['sort']);
        $this->assertSame('abc123', $data['images'][0]['placeholder_hash']);
    }

    public function test_from_model_without_images_serializes_empty_images_array(): void
    {
        $bar = Bar::factory()->create();
        $glass = Glass::factory()->for($bar)->create([
            'name' => 'Coupe',
            'description' => null,
            'volume' => null,
            'volume_units' => null,
        ]);

        $external = GlassExternal::fromModel($glass);

        $data = $external->toDataPackArray();

        $this->assertSame('Coupe', $data['name']);
        $this->assertNull($data['description']);
        $this->assertNull($data['volume']);
        $this->assertNull($data['volume_units']);
        $this->assertSame([], $data['images']);
    }

    public function test_from_datapack_array_round_trips(): void
    {
        $source = [
            '_id' => 'highball_1',
            'name' => 'Highball',
            'description' => 'A tall glass',
            'volume' => 350.0,
            'volume_units' => 'ml',
            'images' => [
                [
                    'uri' => 'file:///g-1.jpg',
                    'sort' => 1,
                    'placeholder_hash' => null,
                    'copyright' => 'Photographer',
                ],
            ],
        ];

        $external = GlassExternal::fromDataPackArray($source);

        $this->assertInstanceOf(ImageExternal::class, $external->images[0]);
        $this->assertSame('/g-1.jpg', $external->images[0]->getLocalFilePath());
        $this->assertSame($source, $external->toDataPackArray());
    }
}
