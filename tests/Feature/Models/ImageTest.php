<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_file_name_returns_correct_value_for_each_imageable_type(): void
    {
        Storage::fake('uploads');
        $bar = Bar::factory()->create();

        $cocktail = Cocktail::factory()->for($bar)->create(['name' => 'Gin and Tonic']);
        $ingredient = Ingredient::factory()->for($bar)->create(['name' => 'Jack Daniels']);
        $glass = Glass::factory()->for($bar)->create(['name' => 'Highball']);

        $cocktailFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));
        $ingredientFile = UploadedFile::fake()->createWithContent('image2.png', $this->getFakeImageContent('png'));
        $glassFile = UploadedFile::fake()->createWithContent('image3.jpg', $this->getFakeImageContent('jpg'));

        $cocktailImage = Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $cocktailFile->storeAs('cocktails/' . $bar->id, 'c-1-img.jpg', 'uploads'),
            'file_extension' => 'jpg',
        ]);
        $ingredientImage = Image::factory()->for($ingredient, 'imageable')->create([
            'file_path' => $ingredientFile->storeAs('ingredients/' . $bar->id, 'i-1-img.png', 'uploads'),
            'file_extension' => 'png',
        ]);
        $glassImage = Image::factory()->for($glass, 'imageable')->create([
            'file_path' => $glassFile->storeAs('glasses/' . $bar->id, 'g-1-img.jpg', 'uploads'),
            'file_extension' => 'jpg',
        ]);

        $this->assertSame('c-1-img.jpg', $cocktailImage->getFileName());
        $this->assertSame('i-1-img.png', $ingredientImage->getFileName());
        $this->assertSame('g-1-img.jpg', $glassImage->getFileName());

        $this->assertSame('file:///c-1-img.jpg', $cocktailImage->getImageAsFileURI());
        $this->assertSame('file:///i-1-img.png', $ingredientImage->getImageAsFileURI());
        $this->assertSame('file:///g-1-img.jpg', $glassImage->getImageAsFileURI());

        $this->assertSame('c-1-img.jpg', $cocktailImage->getExternalId());
        $this->assertSame('i-1-img.png', $ingredientImage->getExternalId());
        $this->assertSame('g-1-img.jpg', $glassImage->getExternalId());
    }

    public function test_get_external_id_falls_back_to_numeric_id_when_file_path_is_empty(): void
    {
        $bar = Bar::factory()->create();
        $glass = Glass::factory()->for($bar)->create();
        $image = Image::factory()->for($glass, 'imageable')->create(['file_path' => '']);

        $this->assertNull($image->getFileName());
        $this->assertNull($image->getImageAsFileURI());
        $this->assertSame((string) $image->id, $image->getExternalId());
    }

    public function test_get_all_bar_images_includes_glass_cocktail_and_ingredient_images(): void
    {
        Storage::fake('uploads');
        $bar = Bar::factory()->create();

        $cocktail = Cocktail::factory()->for($bar)->create();
        $ingredient = Ingredient::factory()->for($bar)->create();
        $glass = Glass::factory()->for($bar)->create();

        Image::factory()->for($cocktail, 'imageable')->create(['file_path' => 'cocktails/' . $bar->id . '/c.jpg']);
        Image::factory()->for($ingredient, 'imageable')->create(['file_path' => 'ingredients/' . $bar->id . '/i.png']);
        Image::factory()->for($glass, 'imageable')->create(['file_path' => 'glasses/' . $bar->id . '/g.jpg']);

        $images = (new Image())->getAllBarImages($bar->id);

        $this->assertCount(3, $images);
        $types = $images->pluck('imageable_type')->unique()->values()->all();
        $this->assertContains(Cocktail::class, $types);
        $this->assertContains(Ingredient::class, $types);
        $this->assertContains(Glass::class, $types);
    }
}
