<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Utensil;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UtensilControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_list_all_utensils_response()
    {
        $response = $this->getJson('/api/utensils');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 20)
                ->etc()
        );
    }

    public function test_show_utensil_response()
    {
        $utensil = Utensil::factory()->create([
            'name' => 'Utensil 1',
            'description' => 'Utensil 1 Description',
        ]);

        $response = $this->getJson('/api/utensils/' . $utensil->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Utensil 1')
                ->where('data.description', 'Utensil 1 Description')
                ->etc()
        );
    }

    public function test_save_utensil_response()
    {
        $response = $this->postJson('/api/utensils/', [
            'name' => 'Utensil 1',
            'description' => 'Utensil 1 Description',
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Utensil 1')
                ->where('data.description', 'Utensil 1 Description')
                ->etc()
        );
    }

    public function test_save_utensil_forbidden_response()
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $response = $this->postJson('/api/utensils/', [
            'name' => 'Utensil 1'
        ]);

        $response->assertForbidden();
    }

    public function test_update_utensil_response()
    {
        $utensil = Utensil::factory()->create([
            'name' => 'Utensil 1',
            'description' => 'Utensil 1 Description',
        ]);

        $response = $this->putJson('/api/utensils/' . $utensil->id, [
            'name' => 'Utensil updated',
            'description' => 'Utensil updated Description',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Utensil updated')
                ->where('data.description', 'Utensil updated Description')
                ->etc()
        );
    }

    public function test_delete_utensil_response()
    {
        $utensil = Utensil::factory()->create([
            'name' => 'Utensil 1',
            'description' => 'Utensil 1 Description',
        ]);

        $response = $this->deleteJson('/api/utensils/' . $utensil->id);

        $response->assertNoContent();
    }
}
