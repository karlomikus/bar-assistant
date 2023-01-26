<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CocktailMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_methods_response()
    {
        $response = $this->getJson('/api/cocktail-methods');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 6)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_show_method_response()
    {
        $model = CocktailMethod::factory()->create([
            'name' => 'Test method'
        ]);

        $response = $this->getJson('/api/cocktail-methods/' . $model->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test method')
                ->has('data.dilution_percentage')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_create_method_response()
    {
        $response = $this->postJson('/api/cocktail-methods/', [
            'name' => 'Test method',
            'dilution_percentage' => 32,
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test method')
                ->where('data.dilution_percentage', 32)
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_update_method_response()
    {
        $model = CocktailMethod::factory()->create([
            'name' => 'Start method',
            'dilution_percentage' => 32,
        ]);

        $response = $this->putJson('/api/cocktail-methods/' . $model->id, [
            'name' => 'Test method',
            'dilution_percentage' => 12,
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $model->id)
                ->where('data.name', 'Test method')
                ->where('data.dilution_percentage', 12)
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_delete_method_response()
    {
        $method = CocktailMethod::factory()->create([
            'name' => 'Start method',
        ]);

        $response = $this->delete('/api/cocktail-methods/' . $method->id);

        $response->assertNoContent();

        $response->assertValidResponse();

        $this->assertDatabaseMissing('cocktail_methods', ['id' => $method->id]);
    }
}
