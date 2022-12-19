<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
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

        Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_rate_cocktail_response()
    {
        $cocktail = Cocktail::factory()->create();

        $response = $this->postJson('/api/ratings/cocktails/' . $cocktail->id, [
            'rating' => 3
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.rating', 3)
                ->where('data.rateable_id', $cocktail->id)
                ->etc()
        );

        $response->assertValidResponse();
        $response->assertValidRequest();
    }
}
