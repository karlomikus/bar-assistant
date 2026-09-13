<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Review;

use DomainException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Review\Recommendation;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Review\IngredientReview;
use BarAssistant\Domain\Review\IngredientReviewId;

final class IngredientReviewTest extends TestCase
{
    private function makeReview(int $ingredientId = 1, int $memberId = 10, ?Recommendation $recommendation = null): IngredientReview
    {
        return IngredientReview::create(
            ingredientId: $ingredientId,
            memberId: new MemberId($memberId),
            content: ReviewContent::create('Smoky and complex'),
            recommendation: $recommendation,
            recordTimestamps: RecordTimestamps::createdNow(),
        );
    }

    public function test_create_produces_transient_review(): void
    {
        $review = $this->makeReview();

        $this->assertTrue($review->isTransient());
        $this->assertNull($review->getId());
    }

    public function test_create_stores_ingredient_and_member(): void
    {
        $review = $this->makeReview(ingredientId: 42, memberId: 99);

        $this->assertSame(42, $review->getIngredientId());
        $this->assertSame(99, $review->getMemberId()->value);
    }

    public function test_create_stores_content_and_recommendation(): void
    {
        $review = $this->makeReview(recommendation: Recommendation::Recommend);

        $this->assertSame('Smoky and complex', $review->getContent()->value);
        $this->assertSame(Recommendation::Recommend, $review->getRecommendation());
    }

    public function test_set_id_marks_review_as_persisted(): void
    {
        $review = $this->makeReview();
        $review->setId(new IngredientReviewId(5));

        $this->assertFalse($review->isTransient());
        $this->assertSame(5, $review->getId()?->value);
    }

    public function test_set_id_on_persisted_review_throws(): void
    {
        $review = $this->makeReview();
        $review->setId(new IngredientReviewId(5));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing ingredient review');

        $review->setId(new IngredientReviewId(6));
    }

    public function test_update_replaces_content_and_recommendation(): void
    {
        $review = $this->makeReview(recommendation: Recommendation::Avoid);

        $review->update(ReviewContent::create('Revisited'), null);

        $this->assertSame('Revisited', $review->getContent()->value);
        $this->assertNull($review->getRecommendation());
        $this->assertTrue($review->getRecordTimestamps()->wasUpdated());
    }
}
