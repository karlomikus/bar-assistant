<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\Cocktail as ModelCocktail;
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
        $this->assertSame(4, $foundRating->getValue()->value);

        $repository->delete($savedRating->getId() ?? new \BarAssistant\Domain\Rating\RatingId(0));

        $this->assertDatabaseMissing('ratings', ['id' => $savedRating->getId()?->value]);
    }
}
