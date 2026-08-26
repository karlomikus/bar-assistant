<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewControllerTest extends TestCase
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

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => 'A well balanced drink.',
        ], $this->headers($membership->bar_id));

        $response->assertSuccessful();
        $this->assertDatabaseHas('cocktail_reviews', [
            'cocktail_id' => $cocktail->id,
            'bar_membership_id' => $membership->id,
            'content' => 'A well balanced drink.',
        ]);
    }

    public function test_member_cannot_create_second_review_for_same_cocktail(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => 'First review',
        ], $this->headers($membership->bar_id))->assertSuccessful();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => 'Second review',
        ], $this->headers($membership->bar_id));

        $response->assertStatus(409);
        $this->assertDatabaseHas('cocktail_reviews', ['cocktail_id' => $cocktail->id, 'content' => 'First review']);
    }

    public function test_empty_content_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => '',
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('cocktail_reviews', ['cocktail_id' => $cocktail->id]);
    }

    public function test_whitespace_only_content_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => '   ',
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('cocktail_reviews', ['cocktail_id' => $cocktail->id]);
    }

    public function test_content_over_5000_characters_is_rejected(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => str_repeat('a', 5001),
        ], $this->headers($membership->bar_id));

        $response->assertUnprocessable();
    }

    public function test_content_at_5000_character_limit_is_accepted(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->postJson('/api/cocktails/' . $cocktail->id . '/reviews', [
            'content' => str_repeat('a', 5000),
        ], $this->headers($membership->bar_id));

        $response->assertSuccessful();
        $this->assertDatabaseHas('cocktail_reviews', ['cocktail_id' => $cocktail->id, 'content' => str_repeat('a', 5000)]);
    }

    public function test_list_returns_reviews_newest_first(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->createReviewFromNewMember($membership, $cocktail, 'First', now()->subMinutes(2));
        $this->createReviewFromNewMember($membership, $cocktail, 'Second', now()->subMinute());
        $this->createReviewFromNewMember($membership, $cocktail, 'Third', now());

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $contents = array_map(fn ($r) => $r['content'], $response->json('data'));
        $this->assertSame(['Third', 'Second', 'First'], $contents);
    }

    public function test_list_is_paginated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        for ($i = 0; $i < 16; $i++) {
            $this->createReviewFromNewMember($membership, $cocktail, 'Review ' . $i, now()->subMinutes(16 - $i));
        }

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertCount(15, $response->json('data'));
        $this->assertSame(16, $response->json('meta.total'));
    }

    public function test_author_can_delete_their_review(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $reviewId = $this->createReview($membership, $cocktail, 'Original');

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/reviews/' . $reviewId, [], $this->headers($membership->bar_id));

        $response->assertNoContent();
        $this->assertDatabaseMissing('cocktail_reviews', ['id' => $reviewId]);
    }

    public function test_bar_admin_can_delete_any_review(): void
    {
        $admin = $this->setupBarMembership(UserRoleEnum::Admin);
        $general = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($admin->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        $cocktail = Cocktail::factory()->for($admin->bar)->create();
        $reviewId = $this->createReview($general, $cocktail, 'Member review');

        $this->actingAs($admin->user);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/reviews/' . $reviewId, [], $this->headers($admin->bar_id));

        $response->assertNoContent();
        $this->assertDatabaseMissing('cocktail_reviews', ['id' => $reviewId]);
    }

    public function test_non_admin_member_cannot_delete_others_review(): void
    {
        $author = $this->setupBarMembership(UserRoleEnum::Admin);
        $general = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($author->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        $cocktail = Cocktail::factory()->for($author->bar)->create();
        $reviewId = $this->createReview($author, $cocktail, 'Author review');

        $this->actingAs($general->user);

        $response = $this->deleteJson('/api/cocktails/' . $cocktail->id . '/reviews/' . $reviewId, [], $this->headers($author->bar_id));

        $response->assertForbidden();
        $this->assertDatabaseHas('cocktail_reviews', ['id' => $reviewId]);
    }

    public function test_non_member_is_denied(): void
    {
        $member = $this->setupBarMembership();
        $nonMember = $this->setupBarMembership();

        $cocktail = Cocktail::factory()->for($member->bar)->create();

        $this->actingAs($nonMember->user);

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($member->bar_id));

        $response->assertForbidden();
    }

    public function test_review_includes_live_author_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $cocktail->rate(4.5, $membership->id);

        $this->createReview($membership, $cocktail, 'Great');

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame(4.5, (float) $response->json('data.0.rating'));
    }

    public function test_review_rating_is_null_when_author_unrated(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->createReview($membership, $cocktail, 'No rating');

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertNull($response->json('data.0.rating'));
    }

    public function test_review_includes_author_identity(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $this->createReview($membership, $cocktail, 'With author');

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertOk();
        $this->assertSame($membership->user->id, $response->json('data.0.author.id'));
        $this->assertSame($membership->user->name, $response->json('data.0.author.name'));
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $membership = $this->setupBarMembership();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();

        $response = $this->getJson('/api/cocktails/' . $cocktail->id . '/reviews', $this->headers($membership->bar_id));

        $response->assertUnauthorized();
    }

    public function test_deleting_review_keeps_rating(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $cocktail->rate(3.0, $membership->id);
        $reviewId = $this->createReview($membership, $cocktail, 'With rating');

        $this->deleteJson('/api/cocktails/' . $cocktail->id . '/reviews/' . $reviewId, [], $this->headers($membership->bar_id))->assertNoContent();

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $cocktail->id,
            'bar_membership_id' => $membership->id,
            'rating' => 3.0,
        ]);
        $this->assertDatabaseMissing('cocktail_reviews', ['id' => $reviewId]);
    }

    private function createReview(BarMembership $membership, Cocktail $cocktail, string $content, $createdAt = null): int
    {
        $review = \Kami\Cocktail\Models\CocktailReview::factory()->create([
            'cocktail_id' => $cocktail->id,
            'bar_membership_id' => $membership->id,
            'content' => $content,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        return $review->id;
    }

    /**
     * Create a review from a fresh member of the same bar (the unique constraint
     * `[bar_membership_id, cocktail_id]` allows only one review per member per cocktail).
     */
    private function createReviewFromNewMember(BarMembership $hostMembership, Cocktail $cocktail, string $content, $createdAt = null): int
    {
        $newMember = BarMembership::factory()
            ->for(User::factory()->create(), 'user')
            ->for($hostMembership->bar)
            ->create(['user_role_id' => UserRoleEnum::General->value]);

        return $this->createReview($newMember, $cocktail, $content, $createdAt);
    }
}
