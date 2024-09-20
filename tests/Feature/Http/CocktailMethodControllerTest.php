<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CocktailMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_methods_response(): void
    {
        CocktailMethod::factory()->recycle($this->barMembership->bar)->count(6)->create();
        CocktailMethod::factory()->count(6)->create();

        $response = $this->getJson('/api/cocktail-methods', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 6)
                ->etc()
        );
    }

    public function test_show_method_response(): void
    {
        $model = CocktailMethod::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Test method',
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
    }

    public function test_create_method_response(): void
    {
        $response = $this->postJson('/api/cocktail-methods', [
            'name' => 'Test method',
            'dilution_percentage' => 32,
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

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
    }

    public function test_update_method_response(): void
    {
        $model = CocktailMethod::factory()->recycle($this->barMembership->bar)->create([
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
    }

    public function test_delete_method_response(): void
    {
        $method = CocktailMethod::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Start method',
        ]);

        $response = $this->delete('/api/cocktail-methods/' . $method->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cocktail_methods', ['id' => $method->id]);
    }
}
