<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
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

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_methods_response(): void
    {
        $bar = $this->setupBar();
        CocktailMethod::factory()->count(6)->create(['bar_id' => $bar->id]);
        $anotherBar = Bar::factory()->create(['created_user_id' => auth()->user()->id]);
        CocktailMethod::factory()->count(6)->create(['bar_id' => $anotherBar->id]);

        $response = $this->getJson('/api/cocktail-methods?bar_id=1');

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
        $bar = $this->setupBar();

        $model = CocktailMethod::factory()->create([
            'name' => 'Test method',
            'bar_id' => $bar->id,
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
        $this->setupBar();

        $response = $this->postJson('/api/cocktail-methods?bar_id=1', [
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
    }

    public function test_update_method_response(): void
    {
        $bar = $this->setupBar();

        $model = CocktailMethod::factory()->create([
            'name' => 'Start method',
            'dilution_percentage' => 32,
            'bar_id' => $bar->id,
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
        $bar = $this->setupBar();

        $method = CocktailMethod::factory()->create([
            'name' => 'Start method',
            'bar_id' => $bar->id,
        ]);

        $response = $this->delete('/api/cocktail-methods/' . $method->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cocktail_methods', ['id' => $method->id]);
    }
}
