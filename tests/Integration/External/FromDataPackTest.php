<?php

declare(strict_types=1);

namespace Tests\Integration\External;

use Tests\TestCase;
use Kami\Cocktail\Models\Glass;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\BarOptionsEnum;
use Kami\Cocktail\External\Import\FromDataPack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\External\Import\DataPackMediaMode;
use Kami\Cocktail\Services\Image\StarterMediaCatalogService;

class FromDataPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_all_data(): void
    {
        $membership = $this->setupBarMembership();

        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack'),
        ]);

        $this->assertDatabaseEmpty('glasses');
        $this->assertDatabaseEmpty('cocktail_methods');
        $this->assertDatabaseEmpty('price_categories');
        $this->assertDatabaseEmpty('utensils');

        $importer = resolve(FromDataPack::class);
        $importer->process($datapackFolder, $membership->bar_id, $membership->user_id, BarOptionsEnum::Cocktails);

        $glass1 = DB::table('glasses')->where('name', 'glass 1')->first();
        $this->assertNotNull($glass1);
        $this->assertSame('glass 1 description', $glass1->description);
        $this->assertSame(350.0, (float) $glass1->volume);
        $this->assertSame('ml', $glass1->volume_units);

        $glass2 = DB::table('glasses')->where('name', 'glass 2')->first();
        $this->assertNotNull($glass2);
        $this->assertNull($glass2->description);

        $this->assertDatabaseHas('images', [
            'imageable_type' => Glass::class,
            'imageable_id' => $glass1->id,
            'copyright' => 'Glass image copyright',
            'sort' => 1,
            'placeholder_hash' => null,
        ]);
        $glass1Image = DB::table('images')->where('imageable_type', Glass::class)->where('imageable_id', $glass1->id)->first();
        $this->assertNotNull($glass1Image);
        $this->assertStringStartsWith('glasses/' . $membership->bar->id . '/', $glass1Image->file_path);
        $this->assertTrue(Storage::disk('uploads')->exists($glass1Image->file_path));
        $this->assertSame('uploads', $glass1Image->disk);
        $this->assertSame('owned', $glass1Image->storage_origin);
        $this->assertDatabaseMissing('images', ['imageable_type' => Glass::class, 'imageable_id' => $glass2->id]);

        $this->assertDatabaseHas('cocktail_methods', ['name' => 'method 1', 'dilution_percentage' => 15]);
        $this->assertDatabaseHas('cocktail_methods', ['name' => 'method 2', 'dilution_percentage' => 0]);

        $this->assertDatabaseHas('price_categories', ['name' => 'price 1', 'currency' => 'NAM', 'description' => 'Price description']);
        $this->assertDatabaseHas('price_categories', ['name' => 'price 2', 'currency' => 'TKL', 'description' => null]);

        $this->assertDatabaseHas('utensils', ['name' => 'utensil 1', 'description' => 'utensil description']);

        $this->assertDatabaseHas('images', [
            'imageable_type' => Ingredient::class,
            'imageable_id' => 1,
            'copyright' => 'Image copyright',
            'sort' => 1,
            'placeholder_hash' => null,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'bar_id' => $membership->bar->id,
            'created_user_id' => $membership->user->id,
            'name' => 'Test ingredient',
            'strength' => 37.75,
            'description' => 'Description of ingredient',
            'origin' => 'French Guiana',
            'color' => '#b474de',
            'created_at' => '1976-01-23T22:25:11+00:00',
            'updated_at' => '1998-01-08T13:41:44+00:00'
        ]);

        $this->assertDatabaseHas('images', [
            'imageable_type' => Cocktail::class,
            'imageable_id' => 1,
            'copyright' => 'Random image',
            'sort' => 1,
            'placeholder_hash' => null,
        ]);
        $glass2 = DB::table('glasses')->where('name', 'glass 2')->first();
        $this->assertDatabaseHas('cocktails', [
            'bar_id' => $membership->bar->id,
            'created_user_id' => $membership->user->id,
            'name' => 'Test cocktail',
            'instructions' => 'Cocktail instructions',
            'description' => 'Cocktail description',
            'source' => 'http://www.bins.org/fugiat-reprehenderit-necessitatibus-sapiente-quia',
            'garnish' => 'Lemon wheel',
            'abv' => 37.77,
            'created_at' => '1979-12-23T09:07:48+00:00',
            'updated_at' => '1983-01-24T11:37:19+00:00',
            'glass_id' => $glass2->id,
            'cocktail_method_id' => 1,
        ]);
    }

    public function test_import_legacy_glasses_datapack_imports_without_images(): void
    {
        $membership = $this->setupBarMembership();

        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack-legacy'),
        ]);

        $this->assertDatabaseEmpty('glasses');

        $importer = resolve(FromDataPack::class);
        $importer->process($datapackFolder, $membership->bar_id, $membership->user_id);

        $this->assertDatabaseHas('glasses', ['name' => 'glass 1', 'description' => 'glass 1 description', 'bar_id' => $membership->bar->id]);
        $this->assertDatabaseHas('glasses', ['name' => 'glass 2', 'description' => null, 'bar_id' => $membership->bar->id]);
        $this->assertDatabaseMissing('images', ['imageable_type' => Glass::class]);
    }

    public function test_catalog_import_creates_independent_attachments_that_share_catalog_objects(): void
    {
        Storage::fake('catalog');
        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack'),
        ]);
        $catalogService = resolve(StarterMediaCatalogService::class);
        $version = $catalogService->releaseVersion($datapackFolder);
        $objects = $catalogService->sourceObjects($datapackFolder);

        foreach ($objects as $sourcePath => $object) {
            $stream = $datapackFolder->readStream($sourcePath);
            $this->assertIsResource($stream);
            Storage::disk('catalog')->writeStream('catalog/' . $version . '/' . $object['key'], $stream);
            fclose($stream);
        }
        Storage::disk('catalog')->put(
            $catalogService->completionManifestPath($version),
            json_encode(['version' => $version, 'objects' => $objects], JSON_THROW_ON_ERROR),
        );

        $firstMembership = $this->setupBarMembership();
        $secondMembership = $this->setupBarMembership();
        $importer = resolve(FromDataPack::class);

        $importer->process($datapackFolder, $firstMembership->bar_id, $firstMembership->user_id, BarOptionsEnum::Cocktails, DataPackMediaMode::StarterCatalog);
        $importer->process($datapackFolder, $secondMembership->bar_id, $secondMembership->user_id, BarOptionsEnum::Cocktails, DataPackMediaMode::StarterCatalog);

        $firstImage = DB::table('images')->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')->where('images.imageable_type', Cocktail::class)->where('cocktails.bar_id', $firstMembership->bar_id)->first();
        $secondImage = DB::table('images')->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')->where('images.imageable_type', Cocktail::class)->where('cocktails.bar_id', $secondMembership->bar_id)->first();

        $this->assertNotNull($firstImage);
        $this->assertNotNull($secondImage);
        $this->assertNotSame($firstImage->id, $secondImage->id);
        $this->assertSame($firstImage->file_path, $secondImage->file_path);
        $this->assertSame('catalog', $firstImage->disk);
        $this->assertSame('catalog', $firstImage->storage_origin);

        $importer->process($datapackFolder, $firstMembership->bar_id, $firstMembership->user_id, BarOptionsEnum::Cocktails, DataPackMediaMode::StarterCatalog);

        $this->assertSame(1, DB::table('images')->join('cocktails', 'cocktails.id', '=', 'images.imageable_id')->where('images.imageable_type', Cocktail::class)->where('cocktails.bar_id', $firstMembership->bar_id)->count());
    }

    public function test_catalog_import_rejects_an_incomplete_release_before_creating_rows(): void
    {
        Storage::fake('catalog');
        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack'),
        ]);
        $catalogService = resolve(StarterMediaCatalogService::class);
        $version = $catalogService->releaseVersion($datapackFolder);
        $objects = $catalogService->sourceObjects($datapackFolder);
        Storage::disk('catalog')->put(
            $catalogService->completionManifestPath($version),
            json_encode(['version' => $version, 'objects' => $objects], JSON_THROW_ON_ERROR),
        );
        $membership = $this->setupBarMembership();

        try {
            resolve(FromDataPack::class)->process($datapackFolder, $membership->bar_id, $membership->user_id, BarOptionsEnum::Cocktails, DataPackMediaMode::StarterCatalog);
            $this->fail('Expected incomplete catalog release to be rejected');
        } catch (\RuntimeException) {
        }

        $this->assertDatabaseEmpty('images');
    }

    public function test_completed_catalog_release_rejects_changed_source_media(): void
    {
        Storage::fake('catalog');
        $datapackFolder = Storage::build([
            'driver' => 'local',
            'root' => base_path('tests/fixtures/datapack'),
        ]);
        $catalogService = resolve(StarterMediaCatalogService::class);
        $version = $catalogService->releaseVersion($datapackFolder);
        $objects = $catalogService->sourceObjects($datapackFolder);

        foreach ($objects as $sourcePath => $object) {
            $stream = $datapackFolder->readStream($sourcePath);
            $this->assertIsResource($stream);
            Storage::disk('catalog')->writeStream('catalog/' . $version . '/' . $object['key'], $stream);
            fclose($stream);
        }
        Storage::disk('catalog')->put(
            $catalogService->completionManifestPath($version),
            json_encode(['version' => $version, 'objects' => $objects], JSON_THROW_ON_ERROR),
        );
        $firstPath = array_key_first($objects);
        $this->assertIsString($firstPath);
        Storage::disk('catalog')->put('catalog/' . $version . '/' . $objects[$firstPath]['key'], 'changed media');

        $this->expectException(\RuntimeException::class);
        $catalogService->assertCompletedRelease(Storage::disk('catalog'), $version, $objects);
    }
}
