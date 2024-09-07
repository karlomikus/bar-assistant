<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RatingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_cocktail(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/ratings', [
            'rating' => 3
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $cocktail->id,
            'user_id' => $membership->user_id,
            'rating' => 3
        ]);
    }

    public function test_rating_cocktail_updates_existing_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $cocktail->rate(2, $membership->user_id);

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/ratings', [
            'rating' => 4
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $cocktail->id,
            'user_id' => $membership->user_id,
            'rating' => 4
        ]);
    }

    public function test_delete_cocktail_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $cocktail->rate(2, $membership->user_id);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/ratings');

        $this->assertDatabaseMissing('ratings', ['rateable_id' => $cocktail->id]);
        $response->assertNoContent();
    }
}
