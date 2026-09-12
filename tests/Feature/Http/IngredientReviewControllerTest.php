<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\TasteDescriptor;
use Kami\Cocktail\Models\IngredientReview;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function headers(int $barId): array
    {
        return ['Bar-Assistant-Bar-Id' => (string) $barId];
    }

    public function test_member_creates_a_review(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Smoky and complex.',
            'recommendation' => 'recommend',
        ], $this->headers($membership->bar_id));

        $response->assertCreated();
        $this->assertDatabaseHas('ingredient_reviews', [
            'ingredient_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
            'content' => 'Smoky and complex.',
            'recommendation' => 'recommend',
        ]);
    }

    public function test_member_cannot_create_second_review_for_same_ingredient(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'First review',
        ], $this->headers($membership->bar_id))->assertCreated();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Second review',
        ], $this->headers($membership->bar_id));

        $response->assertStatus(409);
        $this->assertDatabaseHas('ingredient_reviews', ['ingredient_id' => $ingredient->id, 'content' => 'First review']);
    }

    public function test_empty_content_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => '',
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('ingredient_reviews', ['ingredient_id' => $ingredient->id]);
    }

    public function test_whitespace_only_content_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => '   ',
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('ingredient_reviews', ['ingredient_id' => $ingredient->id]);
    }

    public function test_content_over_5000_characters_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => str_repeat('a', 5001),
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
    }

    public function test_content_at_5000_character_limit_is_accepted(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => str_repeat('a', 5000),
        ], $this->headers($membership->bar_id));

        $response->assertCreated();
        $this->assertDatabaseHas('ingredient_reviews', ['ingredient_id' => $ingredient->id, 'content' => str_repeat('a', 5000)]);
    }

    public function test_invalid_recommendation_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Hmm',
            'recommendation' => 'maybe',
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('ingredient_reviews', ['ingredient_id' => $ingredient->id]);
    }

    public function test_recommendation_may_be_omitted(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'No verdict',
        ], $this->headers($membership->bar_id))->assertCreated();

        $this->assertDatabaseHas('ingredient_reviews', [
            'ingredient_id' => $ingredient->id,
            'recommendation' => null,
        ]);
    }

    public function test_descriptors_are_attached_and_reused_case_insensitively(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'First',
            'taste_descriptors' => ['Smoky', 'Peaty'],
        ], $this->headers($membership->bar_id))->assertCreated();

        $secondMember = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($membership->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        $this->actingAs($secondMember->user);
        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Second',
            'taste_descriptors' => ['  smoky  '],
        ], $this->headers($membership->bar_id))->assertCreated();

        $this->assertSame(2, TasteDescriptor::where('bar_id', $membership->bar_id)->count());
        $smoky = TasteDescriptor::where('bar_id', $membership->bar_id)->where('normalized_name', 'smoky')->firstOrFail();
        $this->assertSame('Smoky', $smoky->name);
        $this->assertSame(2, $smoky->ingredientReviews()->count());
    }

    public function test_updating_replaces_and_clears_descriptors(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'First',
            'taste_descriptors' => ['Smoky', 'Peaty'],
        ], $this->headers($membership->bar_id))->assertCreated();

        $reviewId = IngredientReview::where('ingredient_id', $ingredient->id)->firstOrFail()->id;

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [
            'content' => 'Updated',
            'taste_descriptors' => ['Sweet'],
        ], $this->headers($membership->bar_id))->assertOk();

        $review = IngredientReview::findOrFail($reviewId);
        $this->assertSame(['Sweet'], $review->tasteDescriptors->pluck('name')->all());

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [
            'content' => 'Cleared',
        ], $this->headers($membership->bar_id))->assertOk();

        $this->assertSame([], $review->fresh()->tasteDescriptors->pluck('name')->all());
    }

    public function test_descriptor_validation_rejects_empty_and_too_long_names(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Bad',
            'taste_descriptors' => ['   '],
        ], $this->headers($membership->bar_id))->assertUnprocessable();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'Bad',
            'taste_descriptors' => [str_repeat('a', 101)],
        ], $this->headers($membership->bar_id))->assertUnprocessable();

        $this->assertDatabaseMissing('ingredient_reviews', ['ingredient_id' => $ingredient->id]);
        $this->assertSame(0, TasteDescriptor::where('bar_id', $membership->bar_id)->count());
    }

    public function test_list_returns_reviews_newest_first_and_paginated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->createReviewFromNewMember($membership, $ingredient, 'First', now()->subMinutes(2));
        $this->createReviewFromNewMember($membership, $ingredient, 'Second', now()->subMinute());
        $this->createReviewFromNewMember($membership, $ingredient, 'Third', now());

        $response = $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $contents = array_map(fn ($r) => $r['content'], $response->json('data'));
        $this->assertSame(['Third', 'Second', 'First'], $contents);
    }

    public function test_list_is_paginated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        for ($i = 0; $i < 16; $i++) {
            $this->createReviewFromNewMember($membership, $ingredient, 'Review ' . $i, now()->subMinutes(16 - $i));
        }

        $response = $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertCount(15, $response->json('data'));
        $this->assertSame(16, $response->json('meta.total'));
    }

    public function test_review_resource_includes_author_and_descriptors(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->postJson('/api/ingredients/' . $ingredient->id . '/reviews', [
            'content' => 'With author',
            'taste_descriptors' => ['Smoky'],
        ], $this->headers($membership->bar_id))->assertCreated();

        $response = $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame($membership->user->id, $response->json('data.0.author.id'));
        $this->assertSame($membership->user->name, $response->json('data.0.author.name'));
        $this->assertSame('Smoky', $response->json('data.0.taste_descriptors.0.name'));
    }

    public function test_review_includes_live_author_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $ingredient->rate(4.5, $membership->id);

        $this->createReview($membership, $ingredient, 'Great');

        $response = $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(4.5, (float) $response->json('data.0.rating'));
    }

    public function test_review_rating_is_null_when_author_unrated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->createReview($membership, $ingredient, 'No rating');

        $response = $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertNull($response->json('data.0.rating'));
    }

    public function test_author_can_update_and_delete_their_review(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $reviewId = $this->createReview($membership, $ingredient, 'Original');

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [
            'content' => 'Updated',
        ], $this->headers($membership->bar_id))->assertOk();

        $this->assertDatabaseHas('ingredient_reviews', ['id' => $reviewId, 'content' => 'Updated']);

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [], $this->headers($membership->bar_id))->assertNoContent();
        $this->assertDatabaseMissing('ingredient_reviews', ['id' => $reviewId]);
    }

    public function test_bar_admin_can_update_and_delete_any_review(): void
    {
        $admin = $this->setupBarMembership(UserRoleEnum::Admin);
        $general = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($admin->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        $ingredient = Ingredient::factory()->for($admin->bar)->create();
        $reviewId = $this->createReview($general, $ingredient, 'Member review');

        $this->actingAs($admin->user);

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [
            'content' => 'Admin edit',
        ], $this->headers($admin->bar_id))->assertOk();

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [], $this->headers($admin->bar_id))->assertNoContent();
        $this->assertDatabaseMissing('ingredient_reviews', ['id' => $reviewId]);
    }

    public function test_non_author_member_cannot_update_or_delete(): void
    {
        $author = $this->setupBarMembership(UserRoleEnum::Admin);
        $general = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($author->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        $ingredient = Ingredient::factory()->for($author->bar)->create();
        $reviewId = $this->createReview($author, $ingredient, 'Author review');

        $this->actingAs($general->user);

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [
            'content' => 'Hacked',
        ], $this->headers($author->bar_id))->assertForbidden();

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [], $this->headers($author->bar_id))->assertForbidden();
        $this->assertDatabaseHas('ingredient_reviews', ['id' => $reviewId, 'content' => 'Author review']);
    }

    public function test_unknown_review_cannot_be_updated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->putJson('/api/ingredients/' . $ingredient->id . '/reviews/9999', [
            'content' => 'Nope',
        ], $this->headers($membership->bar_id))->assertNotFound();
    }

    public function test_deleting_review_keeps_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $ingredient->rate(3.0, $membership->id);
        $reviewId = $this->createReview($membership, $ingredient, 'With rating');

        $this->deleteJson('/api/ingredients/' . $ingredient->id . '/reviews/' . $reviewId, [], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
            'rating' => 3.0,
        ]);
    }

    public function test_non_member_is_denied(): void
    {
        $member = $this->setupBarMembership();
        $nonMember = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($member->bar)->create();

        $this->actingAs($nonMember->user);

        $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($member->bar_id))->assertForbidden();
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->getJson('/api/ingredients/' . $ingredient->id . '/reviews', $this->headers($membership->bar_id))->assertUnauthorized();
    }

    public function test_ingredient_detail_aggregates_taste_descriptors(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredient = Ingredient::factory()->for($membership->bar)->create();

        $this->createReview($membership, $ingredient, 'One', null, ['Sweet']);
        $secondMember = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($membership->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);
        $this->createReview($secondMember, $ingredient, 'Two', null, ['Smoky', 'Sweet']);

        $response = $this->getJson('/api/ingredients/' . $ingredient->id, $this->headers($membership->bar_id));

        $response->assertOk();
        $names = $response->json('data.taste_descriptors');
        $this->assertSame(['Smoky', 'Sweet'], array_column($names, 'name'));
    }

    private function createReview(BarMembership $membership, Ingredient $ingredient, string $content, $createdAt = null, array $descriptors = []): int
    {
        $review = IngredientReview::factory()->create([
            'ingredient_id' => $ingredient->id,
            'bar_membership_id' => $membership->id,
            'content' => $content,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        if ($descriptors !== []) {
            $ids = [];
            foreach ($descriptors as $name) {
                $ids[] = TasteDescriptor::firstOrCreate(
                    ['bar_id' => $membership->bar_id, 'normalized_name' => mb_strtolower($name)],
                    ['name' => $name],
                )->id;
            }
            $review->tasteDescriptors()->sync($ids);
        }

        return $review->id;
    }

    private function createReviewFromNewMember(BarMembership $hostMembership, Ingredient $ingredient, string $content, $createdAt = null): int
    {
        $newMember = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($hostMembership->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        return $this->createReview($newMember, $ingredient, $content, $createdAt);
    }
}
