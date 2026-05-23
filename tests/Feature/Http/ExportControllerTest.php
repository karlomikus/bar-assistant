<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Export;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Jobs\StartTypedExport;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_exports_response(): void
    {
        Export::factory()->recycle($this->barMembership->bar)->recycle($this->barMembership->user)->count(10)->create();

        $response = $this->getJson('/api/exports', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_download_export_response(): void
    {
        $s = Storage::fake('exports');
        $s->putFileAs($this->barMembership->bar_id, UploadedFile::fake()->create('test.zip'), 'test.zip');

        $export = Export::factory()->recycle($this->barMembership->bar)->recycle($this->barMembership->user)->create([
            'filename' => 'test.zip',
            'is_done' => true,
        ]);

        $response = $this->postJson('/api/exports/' . $export->id . '/download');
        $response = $this->getJson($response->json('data.url'));

        $response->assertOk();
    }

    public function test_create_export(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/exports', [
            'bar_id' => $this->barMembership->bar_id,
        ]);

        Queue::assertPushed(StartTypedExport::class);

        $response->assertAccepted();
    }

    public function test_create_export_for_unowned_bar(): void
    {
        $bar = Bar::factory()->create();

        Queue::fake();

        $response = $this->postJson('/api/exports', [
            'bar_id' => $bar->id,
        ]);

        Queue::assertNotPushed(StartTypedExport::class);

        $response->assertForbidden();
    }

    public function test_delete_price_category_response(): void
    {
        $export = Export::factory()->recycle($this->barMembership->bar)->recycle($this->barMembership->user)->create();

        $response = $this->delete('/api/exports/' . $export->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('exports', ['id' => $export->id]);
    }

    public function test_download_export_fails_response(): void
    {
        $s = Storage::fake('exports');
        $s->putFileAs($this->barMembership->bar_id, UploadedFile::fake()->create('test.zip'), 'test.zip');

        $export = Export::factory()->recycle($this->barMembership->bar)->recycle($this->barMembership->user)->create([
            'filename' => 'test.zip',
            'is_done' => false,
        ]);

        $response = $this->postJson('/api/exports/' . $export->id . '/download');

        $response->assertBadRequest();
    }
}
