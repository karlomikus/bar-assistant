<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\Cocktail as ModelCocktail;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use Kami\Cocktail\Infrastructure\EloquentRatingRepository;

final class EloquentRatingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_finds_and_deletes_rating(): void
    {
        $membership = $this->setupBarMembership();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $rating = Rating::create(
            rateableId: new RateableId($cocktail->id),
            type: RateableType::Cocktail,
            memberId: new MemberId($membership->id),
            value: RatingValue::create(4),
        );

        $repository = new EloquentRatingRepository();
        $savedRating = $repository->save($rating);

        $this->assertNotNull($savedRating->getId());
        $this->assertDatabaseHas('ratings', [
            'id' => $savedRating->getId()?->value,
            'rateable_id' => $cocktail->id,
            'rateable_type' => ModelCocktail::class,
            'bar_membership_id' => $membership->id,
            'rating' => 4,
        ]);

        $foundRating = $repository->findMemberRating(
            new RateableId($cocktail->id),
            RateableType::Cocktail,
            new MemberId($membership->id),
        );

        $this->assertNotNull($foundRating);
        $this->assertSame($savedRating->getId()?->value, $foundRating->getId()?->value);
        $this->assertSame(4.0, $foundRating->getValue()->value);

        $repository->delete($savedRating->getId() ?? new \BarAssistant\Domain\Rating\RatingId(0));

        $this->assertDatabaseMissing('ratings', ['id' => $savedRating->getId()?->value]);
    }

    public function test_it_round_trips_half_value_rating(): void
    {
        $membership = $this->setupBarMembership();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $rating = Rating::create(
            rateableId: new RateableId($cocktail->id),
            type: RateableType::Cocktail,
            memberId: new MemberId($membership->id),
            value: RatingValue::create(3.5),
        );

        $repository = new EloquentRatingRepository();
        $savedRating = $repository->save($rating);

        $this->assertDatabaseHas('ratings', [
            'id' => $savedRating->getId()?->value,
            'rateable_id' => $cocktail->id,
            'rateable_type' => ModelCocktail::class,
            'bar_membership_id' => $membership->id,
            'rating' => 3.5,
        ]);

        $foundRating = $repository->findMemberRating(
            new RateableId($cocktail->id),
            RateableType::Cocktail,
            new MemberId($membership->id),
        );

        $this->assertNotNull($foundRating);
        $this->assertSame(3.5, $foundRating->getValue()->value);
    }

    public function test_it_round_trips_ingredient_rating_without_affecting_cocktails(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $cocktail->rate(2, $membership->id);

        $rating = Rating::create(
            rateableId: new RateableId($ingredient->id),
            type: RateableType::Ingredient,
            memberId: new MemberId($membership->id),
            value: RatingValue::create(4.5),
        );

        $repository = new EloquentRatingRepository();
        $savedRating = $repository->save($rating);

        $this->assertDatabaseHas('ratings', [
            'id' => $savedRating->getId()?->value,
            'rateable_id' => $ingredient->id,
            'rateable_type' => ModelIngredient::class,
            'bar_membership_id' => $membership->id,
            'rating' => 4.5,
        ]);

        $foundRating = $repository->findMemberRating(
            new RateableId($ingredient->id),
            RateableType::Ingredient,
            new MemberId($membership->id),
        );

        $this->assertNotNull($foundRating);
        $this->assertSame(4.5, $foundRating->getValue()->value);
        $this->assertSame(RateableType::Ingredient, $foundRating->getType());

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $cocktail->id,
            'rateable_type' => ModelCocktail::class,
            'rating' => 2,
        ]);
    }

    public function test_deleting_ingredient_removes_its_ratings(): void
    {
        $membership = $this->setupBarMembership();
        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $ingredient->rate(4, $membership->id);

        $this->assertDatabaseHas('ratings', [
            'rateable_id' => $ingredient->id,
            'rateable_type' => ModelIngredient::class,
        ]);

        $ingredient->delete();

        $this->assertDatabaseMissing('ratings', [
            'rateable_id' => $ingredient->id,
            'rateable_type' => ModelIngredient::class,
        ]);
    }
}
