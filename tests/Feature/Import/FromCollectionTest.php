<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\External\Import\FromCollection;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FromCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_process_none(): void
    {
        $bar = $this->setupBar();
        $importer = $this->getImporter();

        $importData = json_decode(file_get_contents(base_path('tests/fixtures/import_collection.json')), true);

        $this->assertDatabaseCount('cocktails', 0);
        $collection = $importer->process(
            $importData,
            auth()->user()->id,
            $bar->id,
            DuplicateActionsEnum::None
        );

        $this->assertSame('Test collection', $collection->name);
        $this->assertNull($collection->description);
        $this->assertCount(2, $collection->cocktails);
    }

    public function test_process_skip(): void
    {
        $bar = $this->setupBar();
        $importer = $this->getImporter();

        Cocktail::factory()->create(['bar_id' => $bar->id, 'name' => 'Test 1']);
        Cocktail::factory()->create(['bar_id' => $bar->id, 'name' => 'Test 2']);

        $importData = json_decode(file_get_contents(base_path('tests/fixtures/import_collection.json')), true);

        $this->assertDatabaseCount('cocktails', 2);
        $collection = $importer->process(
            $importData,
            auth()->user()->id,
            $bar->id,
            DuplicateActionsEnum::Skip
        );

        $this->assertDatabaseCount('cocktails', 2);
        $this->assertCount(2, $collection->cocktails);
    }

    public function test_process_overwrite(): void
    {
        $bar = $this->setupBar();
        $importer = $this->getImporter();

        $cocktail1 = Cocktail::factory()->create(['bar_id' => $bar->id, 'name' => 'Test 1', 'instructions' => 'Original']);
        $cocktail2 = Cocktail::factory()->create(['bar_id' => $bar->id, 'name' => 'Test 2', 'instructions' => 'Original']);

        $importData = json_decode(file_get_contents(base_path('tests/fixtures/import_collection.json')), true);

        $this->assertDatabaseCount('cocktails', 2);
        $collection = $importer->process(
            $importData,
            auth()->user()->id,
            $bar->id,
            DuplicateActionsEnum::Overwrite
        );

        $this->assertDatabaseCount('cocktails', 2);
        $this->assertCount(2, $collection->cocktails);
        $this->assertSame('Lorem ipsum 1', $cocktail1->fresh()->instructions);
        $this->assertSame('Lorem ipsum 2', $cocktail2->fresh()->instructions);
    }

    private function getImporter(): FromCollection
    {
        return resolve(FromCollection::class);
    }
}
