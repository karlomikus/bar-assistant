<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientRatingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function headers(int $barId): array
    {
        return ['Bar-Assistant-Bar-Id' => (string) $barId];
    }

    public function test_rate_ingredient(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 3,
        ], $this->headers($membership->bar_id));

        $response->assertNoContent();
        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $ingredient->id,
            'rateable_type' => Ingredient::class,
            'bar_membership_id' => $membership->id,
            'rating' => 3,
        ]);
    }

    public function test_rate_ingredient_accepts_half_value(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 3.5,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
            'rating' => 3.5,
        ]);
    }

    public function test_rate_ingredient_updates_existing_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $ingredient->rate(2.5, $membership->id);

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 4,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
            'rating' => 4,
        ]);
        $this->assertSame(1, \Kami\Cocktail\Models\Rating::where('rateable_id', $ingredient->id)->where('rateable_type', Ingredient::class)->count());
    }

    public function test_rate_ingredient_toggles_off_when_same_value_submitted(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 3.5,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 3.5,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseMissing('ratings', [
            'rateable_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
        ]);
    }

    public function test_rate_ingredient_rejects_off_grid_value(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 3.7,
        ], $this->headers($membership->bar_id))->assertUnprocessable();

        $this->assertDatabaseMissing('ratings', ['rateable_id' => $ingredient->id]);
    }

    public function test_rate_ingredient_rejects_out_of_range_and_non_numeric_values(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        foreach ([5.5, 0, 'great'] as $value) {
            $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
                'rating' => $value,
            ], $this->headers($membership->bar_id))->assertUnprocessable();
        }

        $this->assertDatabaseMissing('ratings', ['rateable_id' => $ingredient->id]);
    }

    public function test_ingredient_and_cocktail_ratings_are_isolated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 4,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->postJson('/api/cocktails/' . $cocktail->id . '/ratings', [
            'rating' => 2,
        ], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseHas('ratings', ['rateable_id' => $ingredient->id, 'rateable_type' => Ingredient::class, 'rating' => 4]);
        $this->assertDatabaseHas('ratings', ['rateable_id' => $cocktail->id, 'rateable_type' => Cocktail::class, 'rating' => 2]);

        $response = $this->getJson('/api/ingredients/' . $ingredient->id, $this->headers($membership->bar_id));
        $response->assertOk();
        $this->assertSame(4.0, (float) $response->json('data.rating.user'));
    }

    public function test_unrate_removes_member_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $ingredient->rate(2, $membership->id);

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/ratings', [], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseMissing('ratings', [
            'rateable_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
        ]);
    }

    public function test_unrate_missing_rating_returns_not_found(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/ratings', [], $this->headers($membership->bar_id))->assertNotFound();
    }

    public function test_ingredient_resource_exposes_rating_summary(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $otherOne = $this->addMember($membership);
        $otherTwo = $this->addMember($membership);
        $ingredient->rate(4, $membership->id);
        $ingredient->rate(4, $otherOne->id);
        $ingredient->rate(5, $otherTwo->id);

        $response = $this->getJson('/api/ingredients/' . $ingredient->id, $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(4.0, (float) $response->json('data.rating.user'));
        $this->assertSame(4.5, (float) $response->json('data.rating.average'));
        $this->assertSame(3, $response->json('data.rating.total_votes'));
    }

    public function test_unrated_ingredient_returns_null_user_and_zero_average(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->getJson('/api/ingredients/' . $ingredient->id, $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertNull($response->json('data.rating.user'));
        $this->assertSame(0, $response->json('data.rating.average'));
        $this->assertSame(0, $response->json('data.rating.total_votes'));
    }

    public function test_non_member_is_denied(): void
    {
        $member = $this->setupBarMembership();
        $nonMember = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($member->bar)->create();

        $this->actingAs($nonMember->user);

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 4,
        ], $this->headers($member->bar_id))->assertForbidden();
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/ratings', [
            'rating' => 4,
        ], $this->headers($membership->bar_id))->assertUnauthorized();
    }

    private function addMember(BarMembership $hostMembership): BarMembership
    {
        return BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($hostMembership->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);
    }
}
