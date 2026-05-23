<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\PriceCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PriceCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_price_categories_response(): void
    {
        PriceCategory::factory()->for($this->barMembership->bar)->count(10)->create();

        $response = $this->getJson('/api/price-categories', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_price_category_response(): void
    {
        $cat = PriceCategory::factory()->for($this->barMembership->bar)->create([
            'currency' => 'EUR'
        ]);

        $response = $this->getJson('/api/price-categories/' . $cat->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $cat->id)
                ->where('data.name', $cat->name)
                ->where('data.description', $cat->description)
                ->where('data.currency', $cat->currency)
                ->where('data.currency_symbol', '')
                ->etc()
        );
    }

    public function test_show_price_category_forbidden_when_user_has_no_bar_membership(): void
    {
        $otherBarMembership = $this->setupBarMembership();
        $cat = PriceCategory::factory()->for($otherBarMembership->bar)->create();

        $response = $this->getJson('/api/price-categories/' . $cat->id);

        $response->assertForbidden();
    }

    public function test_create_price_category_response(): void
    {
        $response = $this->postJson('/api/price-categories', [
            'name' => 'Test cat',
            'description' => 'Test cat desc',
            'currency' => 'USD',
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertCreated();
        $response->assertHeader('Location', '/api/price-categories/1');
    }

    public function test_update_price_category_response(): void
    {
        $cat = PriceCategory::factory()->for($this->barMembership->bar)->create();

        $response = $this->putJson('/api/price-categories/' . $cat->id, [
            'name' => 'Test cat',
            'description' => 'Test cat desc',
            'currency' => 'JPY',
        ]);

        $response->assertNoContent();
    }

    public function test_delete_price_category_response(): void
    {
        $cat = PriceCategory::factory()->for($this->barMembership->bar)->create();

        $response = $this->delete('/api/price-categories/' . $cat->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('price_categories', ['id' => $cat->id]);
    }
}
