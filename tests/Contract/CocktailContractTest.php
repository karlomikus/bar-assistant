<?php

namespace Tests\Contract;

use Tests\ContractTestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CocktailContractTest extends ContractTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );

        $this->setupBar();
    }

    public function test_contract_cocktails()
    {
        Cocktail::factory()->count(10)->create(['bar_id' => 1]);

        $response = $this->getJson('/api/cocktails?bar_id=1');
        $response->assertValidResponse(200);
    }

    public function test_contract_cocktail_show()
    {
        $cocktail = Cocktail::factory()->create([
            'name' => 'Test Case',
            'bar_id' => 1
        ]);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id);
        $response->assertValidResponse(200);

        $response = $this->getJson('/api/cocktails/1234567');
        $response->assertValidResponse(404);

        $response = $this->getJson('/api/cocktails/' . $cocktail->slug);
        $response->assertValidResponse(200);
    }

    public function test_contract_cocktail_create()
    {
        $response = $this->postJson('/api/cocktails?bar_id=1', [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step"
        ]);
        $response->assertValidRequest()->assertValidResponse(201);

        $response = $this->postJson('/api/cocktails?bar_id=1', [
            'instructions' => "1. Step\n2. Step"
        ]);
        $response->assertValidRequest()->assertValidResponse(422);
    }

    public function test_contract_cocktail_update()
    {
        $cocktail = Cocktail::factory()->create(['bar_id' => 1, 'created_user_id' => 1]);

        $response = $this->putJson('/api/cocktails/' . $cocktail->id, [
            'name' => "Cocktail name",
            'instructions' => "1. Step\n2. Step"
        ]);
        $response->assertValidRequest()->assertValidResponse(200);

        $response = $this->putJson('/api/cocktails/' . $cocktail->id, [
            'instructions' => "1. Step\n2. Step"
        ]);
        $response->assertValidRequest()->assertValidResponse(422);
    }

    public function test_contract_cocktail_delete()
    {
        $cocktail = Cocktail::factory()->create(['bar_id' => 1, 'created_user_id' => 1]);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id);
        $response->assertValidResponse(204);

        $response = $this->deleteJson('/api/cocktails/124567');
        $response->assertValidResponse(404);
    }
}
