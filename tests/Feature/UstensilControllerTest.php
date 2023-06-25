<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ustensil;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UstensilsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(User::factory()->create());
    }

    public function test_list_all_ustensils_response()
    {
        Ustensil::factory()->count(10)->create();

        $response = $this->getJson('/api/ustensils');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_show_ustensil_response()
    {
        $ustensil = Ustensil::factory()->create([
            'name' => 'Ustensil 1',
            'description' => 'Ustensil 1 Description',
        ]);

        $response = $this->getJson('/api/ustensils/' . $ustensil->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Ustensil 1')
                ->where('data.description', 'Ustensil 1 Description')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_save_ustensil_response()
    {
        $response = $this->postJson('/api/ustensils/', [
            'name' => 'Ustensil 1',
            'description' => 'Ustensil 1 Description',
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Ustensil 1')
                ->where('data.description', 'Ustensil 1 Description')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_save_ustensil_forbidden_response()
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $response = $this->postJson('/api/ustensils/', [
            'name' => 'Ustensil 1'
        ]);

        $response->assertForbidden();
    }

    public function test_update_ustensil_response()
    {
        $ustensil = Ustensil::factory()->create([
            'name' => 'Ustensil 1',
            'description' => 'Ustensil 1 Description',
        ]);

        $response = $this->putJson('/api/ustensils/' . $ustensil->id, [
            'name' => 'Ustensil updated',
            'description' => 'Ustensil updated Description',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Ustensil updated')
                ->where('data.description', 'Ustensil updated Description')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_delete_ustensil_response()
    {
        $ustensil = Ustensil::factory()->create([
            'name' => 'Ustensil 1',
            'description' => 'Ustensil 1 Description',
        ]);

        $response = $this->deleteJson('/api/ustensils/' . $ustensil->id);

        $response->assertNoContent();
        $response->assertValidResponse();
    }
}
