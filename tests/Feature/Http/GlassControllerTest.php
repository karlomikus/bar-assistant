<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlassControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_all_glasses_response(): void
    {
        Glass::factory()->recycle($this->barMembership->bar)->count(10)->create();
        Glass::factory()->count(10)->create();

        $response = $this->getJson('/api/glasses', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_search_glass_by_name_response(): void
    {
        Glass::factory()->recycle($this->barMembership->bar)->count(10)->create();
        Glass::factory()->recycle($this->barMembership->bar)->create(['name' => 'xDEMOx']);

        $response = $this->getJson('/api/glasses?filter[name]=xdemox', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 1)
                ->etc()
        );
    }

    public function test_show_glass_response(): void
    {
        $glass = Glass::factory()
            ->recycle($this->barMembership->bar)
            ->create([
                'name' => 'Glass 1',
                'description' => 'Glass 1 Description',
            ]);

        $response = $this->getJson('/api/glasses/' . $glass->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Glass 1')
                ->where('data.description', 'Glass 1 Description')
                ->etc()
        );
    }

    public function test_save_glass_response(): void
    {
        $image = Image::factory()->create();
        $response = $this->postJson('/api/glasses', [
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
            'volume' => 250,
            'volume_units' => 'ml',
            'images' => [$image->id],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Glass 1')
                ->where('data.description', 'Glass 1 Description')
                ->where('data.volume', 250)
                ->where('data.volume_units', 'ml')
                ->etc()
        );
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'imageable_type' => Glass::class,
            'imageable_id' => $response->json('data.id'),
        ]);
    }

    public function test_save_glass_forbidden_response(): void
    {
        $anotherBar = Bar::factory()->create();

        $response = $this->postJson('/api/glasses', [
            'name' => 'Glass 1'
        ], ['Bar-Assistant-Bar-Id' => $anotherBar->id]);

        $response->assertForbidden();
    }

    public function test_update_glass_response(): void
    {
        $image = Image::factory()->create();
        $glass = Glass::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
            'volume' => 250,
            'volume_units' => 'ml',
        ]);

        $response = $this->putJson('/api/glasses/' . $glass->id, [
            'name' => 'Glass updated',
            'description' => 'Glass updated Description',
            'volume' => 3.2,
            'volume_units' => 'cl',
            'images' => [$image->id],
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Glass updated')
                ->where('data.description', 'Glass updated Description')
                ->where('data.volume', 3.2)
                ->where('data.volume_units', 'cl')
                ->etc()
        );
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'imageable_type' => Glass::class,
            'imageable_id' => $response->json('data.id'),
        ]);
    }

    public function test_delete_glass_response(): void
    {
        $glass = Glass::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
        ]);
        $image = Image::factory()->for($glass, 'imageable')->create();

        $response = $this->deleteJson('/api/glasses/' . $glass->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('images', [
            'id' => $image->id,
            'imageable_type' => Glass::class,
            'imageable_id' => $glass->id,
        ]);
    }
}
