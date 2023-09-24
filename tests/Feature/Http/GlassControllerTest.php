<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Glass;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlassControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_list_all_glasses_response(): void
    {
        $bar = $this->setupBar();

        Glass::factory()->count(10)->create(['bar_id' => $bar->id]);

        $response = $this->getJson('/api/glasses?bar_id=1');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_glass_response(): void
    {
        $this->setupBar();

        $glass = Glass::factory()->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
            'bar_id' => 1,
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
        $this->setupBar();

        $response = $this->postJson('/api/glasses?bar_id=1', [
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Glass 1')
                ->where('data.description', 'Glass 1 Description')
                ->etc()
        );
    }

    public function test_save_glass_forbidden_response(): void
    {
        $this->setupBar();
        $anotherBar = Bar::factory()->create();

        $response = $this->postJson('/api/glasses?bar_id=' . $anotherBar->id, [
            'name' => 'Glass 1'
        ]);

        $response->assertForbidden();
    }

    public function test_update_glass_response(): void
    {
        $bar = $this->setupBar();

        $glass = Glass::factory()->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
            'bar_id' => $bar->id,
        ]);

        $response = $this->putJson('/api/glasses/' . $glass->id, [
            'name' => 'Glass updated',
            'description' => 'Glass updated Description',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Glass updated')
                ->where('data.description', 'Glass updated Description')
                ->etc()
        );
    }

    public function test_delete_glass_response(): void
    {
        $bar = $this->setupBar();

        $glass = Glass::factory()->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
            'bar_id' => $bar->id,
        ]);

        $response = $this->deleteJson('/api/glasses/' . $glass->id);

        $response->assertNoContent();
    }
}
