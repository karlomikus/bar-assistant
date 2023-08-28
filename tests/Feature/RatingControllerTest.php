<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RatingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_rate_cocktail_response()
    {
        $this->setupBar();
        $cocktail = Cocktail::factory()->create(['bar_id' => 1]);

        $response = $this->postJson('/api/ratings/cocktails/' . $cocktail->id, [
            'rating' => 3
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.rating', 3)
                ->where('data.rateable_id', $cocktail->id)
                ->where('data.user_id', 1)
                ->etc()
        );
    }

    public function test_rate_cocktail_updates_existing_rating_response()
    {
        $this->setupBar();
        $cocktail = Cocktail::factory()->create(['bar_id' => 1]);

        $cocktail->rate(2, auth()->user()->id);

        $response = $this->postJson('/api/ratings/cocktails/' . $cocktail->id, [
            'rating' => 4
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.rating', 4)
                ->where('data.rateable_id', $cocktail->id)
                ->where('data.user_id', 1)
                ->etc()
        );
    }

    public function test_delete_cocktail_rating_response()
    {
        $this->setupBar();
        $cocktail = Cocktail::factory()->create(['bar_id' => 1]);
        $cocktail->rate(2, auth()->user()->id);

        $response = $this->delete('/api/ratings/cocktails/' . $cocktail->id);

        $this->assertDatabaseMissing('ratings', ['rateable_id' => $cocktail->id]);
        $response->assertNoContent();
    }
}
