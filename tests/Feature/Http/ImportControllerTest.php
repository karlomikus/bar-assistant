<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    // public function test_import_cocktail_from_json(): void
    // {
    //     $membership = $this->setupBarMembership();
    //     $this->actingAs($membership->user);

    //     $source = file_get_contents(base_path('tests/fixtures/import.json'));

    //     $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
    //     $response = $this->postJson('/api/import/cocktail', [
    //         'schema' => $source
    //     ]);

    //     $response->assertSuccessful();
    // }

    // public function test_import_cocktail_from_json_overwrite_duplicates(): void
    // {
    //     $membership = $this->setupBarMembership();
    //     $this->actingAs($membership->user);

    //     $source = file_get_contents(base_path('tests/fixtures/import.json'));

    //     $cocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Negroni', 'description' => 'To be replaced']);

    //     $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
    //     $response = $this->postJson('/api/import/cocktail', [
    //         'schema' => $source,
    //         'duplicate_actions' => 'overwrite'
    //     ]);

    //     $response->assertSuccessful();
    //     $cocktail->refresh();
    //     $this->assertSame($cocktail->name, 'Negroni');
    //     $this->assertNotSame($cocktail->description, 'To be replaced');
    // }
}
