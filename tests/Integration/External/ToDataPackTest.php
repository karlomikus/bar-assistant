<?php

declare(strict_types=1);

namespace Tests\Integration\External;

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

        $this->assertSame($filename, '1/datapack-test.zip');
    }

    public function test_export_contains_files(): void
    {
        $exportMock = $this->getFileMock();
        Storage::fake('uploads');

        $membership = $this->setupBarMembership();
        Glass::factory()->for($membership->bar)->count(3)->create();
        CocktailMethod::factory()->for($membership->bar)->count(3)->create();
        PriceCategory::factory()->for($membership->bar)->count(3)->create();
        Utensil::factory()->for($membership->bar)->count(3)->create();
        $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Gin and Tonic']);
        $ingredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Jack Daniels']);
        $glassWithImage = Glass::factory()->for($membership->bar)->create(['name' => 'Highball']);
        $glassWithoutImage = Glass::factory()->for($membership->bar)->create(['name' => 'Coupe']);

        $imageCocktailFile = UploadedFile::fake()->createWithContent('image1.jpg', $this->getFakeImageContent('jpg'));
        $ingredientCocktailFile = UploadedFile::fake()->createWithContent('image2.jpg', $this->getFakeImageContent('png'));
        $glassImageFile = UploadedFile::fake()->createWithContent('image3.jpg', $this->getFakeImageContent('jpg'));
        Image::factory()->for($cocktail, 'imageable')->create([
            'file_path' => $imageCocktailFile->storeAs('', 'c-1-img.jpg', 'uploads'),
            'file_extension' => $imageCocktailFile->extension(),
        ]);
        Image::factory()->for($ingredient, 'imageable')->create([
            'file_path' => $ingredientCocktailFile->storeAs('', 'i-1-img.png', 'uploads'),
            'file_extension' => $ingredientCocktailFile->extension(),
        ]);
        Image::factory()->for($glassWithImage, 'imageable')->create([
            'file_path' => $glassImageFile->storeAs('glasses/' . $membership->bar->id, 'g-1-img.jpg', 'uploads'),
            'file_extension' => 'jpg',
            'copyright' => 'Glass image copyright',
            'sort' => 1,
        ]);

        $exporter = new ToDataPack($exportMock);
        $filename = $exporter->process($membership->bar->id, 'datapack-test.zip');
        $filename = $exportMock->path($filename);

        $unzippedFilesDisk = Storage::fake('temp-datapack-unzip');

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \Exception(sprintf('Unable to open zip file: "%s"', $filename));
        }
        $zip->extractTo($unzippedFilesDisk->path('/'));
        $zip->close();

        $expectedFiles = [
            '_meta.json',
            'base_methods.json',
            'base_price_categories.json',
            'base_utensils.json',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists($unzippedFilesDisk->path($file));
        }

        $this->assertFileDoesNotExist($unzippedFilesDisk->path('base_glasses.json'));

        $this->assertFileExists($unzippedFilesDisk->path('cocktails/gin-and-tonic_1/data.json'));
        $this->assertFileExists($unzippedFilesDisk->path('cocktails/gin-and-tonic_1/c-1-img.jpg'));
        $this->assertFileExists($unzippedFilesDisk->path('ingredients/jack-daniels_1/data.json'));
        $this->assertFileExists($unzippedFilesDisk->path('ingredients/jack-daniels_1/i-1-img.png'));

        $glassWithImageDir = 'glasses/' . $glassWithImage->getExternalId();
        $glassWithoutImageDir = 'glasses/' . $glassWithoutImage->getExternalId();
        $this->assertFileExists($unzippedFilesDisk->path($glassWithImageDir . '/data.json'));
        $this->assertFileExists($unzippedFilesDisk->path($glassWithImageDir . '/g-1-img.jpg'));
        $this->assertFileExists($unzippedFilesDisk->path($glassWithoutImageDir . '/data.json'));

        $glassWithImageExport = [];
        if ($glassFixture = file_get_contents($unzippedFilesDisk->path($glassWithImageDir . '/data.json'))) {
            $glassWithImageExport = json_decode($glassFixture, true);
        }
        $glassWithoutImageExport = [];
        if ($glassFixture = file_get_contents($unzippedFilesDisk->path($glassWithoutImageDir . '/data.json'))) {
            $glassWithoutImageExport = json_decode($glassFixture, true);
        }

        $this->assertCount(1, $glassWithImageExport['images']);
        $this->assertSame('file:///g-1-img.jpg', $glassWithImageExport['images'][0]['uri']);
        $this->assertSame('Glass image copyright', $glassWithImageExport['images'][0]['copyright']);
        $this->assertSame([], $glassWithoutImageExport['images']);

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
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function getFileMock()
    {
        $disk = Storage::fake('exports');

        return $disk;
    }
}
