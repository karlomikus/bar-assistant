<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
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

        Spectator::using('open-api-spec.yml');

        $this->actingAs(User::factory()->create());
    }

    public function test_list_all_glasses_response()
    {
        Glass::factory()->count(10)->create();

        $response = $this->getJson('/api/glasses');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_show_glass_response()
    {
        $glass = Glass::factory()->create([
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

        $response->assertValidResponse();
    }

    public function test_save_glass_response()
    {
        $response = $this->postJson('/api/glasses/', [
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

        $response->assertValidResponse();
    }

    public function test_update_glass_response()
    {
        $glass = Glass::factory()->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
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

        $response->assertValidResponse();
    }

    public function test_delete_glass_response()
    {
        $glass = Glass::factory()->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
        ]);

        $response = $this->deleteJson('/api/glasses/' . $glass->id);

        $response->assertNoContent();
        $response->assertValidResponse();
    }
}
