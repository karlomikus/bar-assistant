<?php

declare(strict_types=1);

namespace Tests\Feature\External;

use ZipArchive;
use Tests\TestCase;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\UploadedFile;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Models\CocktailMethod;
use Kami\Cocktail\External\Export\ToDataPack;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ToDataPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_creates_zip_file(): void
    {
        $membership = $this->setupBarMembership();

        $exporter = new ToDataPack($this->getFileMock());
        $filename = $exporter->process($membership->bar->id, 'datapack-test.zip');

        $this->assertTrue(str_ends_with($filename, 'exports/1/datapack-test.zip'));
    }

    public function test_export_contains_files(): void
    {
        Storage::fake('uploads');

        $membership = $this->setupBarMembership();
        Glass::factory()->for($membership->bar)->count(3)->create();
        CocktailMethod::factory()->for($membership->bar)->count(3)->create();
        PriceCategory::factory()->for($membership->bar)->count(3)->create();
        Utensil::factory()->for($membership->bar)->count(3)->create();
        $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Gin and Tonic']);
        $ingredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Jack Daniels']);

        $imageCocktailFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));
        $ingredientCocktailFile = UploadedFile::fake()->createWithContent('image2.jpg', $this->getFakeImageContent('png'));
        Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $imageCocktailFile->storeAs('', 'c-1-img.jpg', 'uploads'),
            'file_extension' => $imageCocktailFile->extension(),
        ]);
        Image::factory()->for($ingredient, 'imageable')->create([
            'file_path' => $ingredientCocktailFile->storeAs('', 'i-1-img.png', 'uploads'),
            'file_extension' => $ingredientCocktailFile->extension(),
        ]);

        $exporter = new ToDataPack($this->getFileMock());
        $filename = $exporter->process($membership->bar->id, 'datapack-test.zip');

        $unzippedFilesDisk = Storage::fake('temp-datapack-unzip');

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \Exception(sprintf('Unable to open zip file: "%s"', $filename));
        }
        $zip->extractTo($unzippedFilesDisk->path('/'));
        $zip->close();

        $expectedFiles = [
            '_meta.json',
            'base_glasses.json',
            'base_methods.json',
            'base_price_categories.json',
            'base_utensils.json',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists($unzippedFilesDisk->path($file));
        }

        $this->assertFileExists($unzippedFilesDisk->path('cocktails/gin-and-tonic_1/data.json'));
        $this->assertFileExists($unzippedFilesDisk->path('cocktails/gin-and-tonic_1/c-1-img.jpg'));
        $this->assertFileExists($unzippedFilesDisk->path('ingredients/jack-daniels_1/data.json'));
        $this->assertFileExists($unzippedFilesDisk->path('ingredients/jack-daniels_1/i-1-img.png'));

        $cocktailExport = [];
        if ($cocktailFixture = file_get_contents($unzippedFilesDisk->path('cocktails/gin-and-tonic_1/data.json'))) {
            $cocktailExport = json_decode($cocktailFixture, true);
        }

        $ingredientExport = [];
        if ($ingredientFixture = file_get_contents($unzippedFilesDisk->path('ingredients/jack-daniels_1/data.json'))) {
            $ingredientExport = json_decode($ingredientFixture, true);
        }

        $this->assertSame('file:///c-1-img.jpg', $cocktailExport['images'][0]['uri']);
        $this->assertSame('file:///i-1-img.png', $ingredientExport['images'][0]['uri']);
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Factory&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFileMock()
    {
        $disk = Storage::fake('exports');
        $factory = $this->createMock(\Illuminate\Contracts\Filesystem\Factory::class);
        $factory->method('disk')->willReturn($disk);

        return $factory;
    }
}
