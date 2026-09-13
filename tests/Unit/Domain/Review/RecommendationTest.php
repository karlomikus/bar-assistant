<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Review;

use ValueError;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Review\Recommendation;

final class RecommendationTest extends TestCase
{
    public function test_case_values(): void
    {
        $this->assertSame('avoid', Recommendation::Avoid->value);
        $this->assertSame('decent', Recommendation::Decent->value);
        $this->assertSame('recommend', Recommendation::Recommend->value);
    }

    public function test_from_resolves_each_case(): void
    {
        $this->assertSame(Recommendation::Avoid, Recommendation::from('avoid'));
        $this->assertSame(Recommendation::Decent, Recommendation::from('decent'));
        $this->assertSame(Recommendation::Recommend, Recommendation::from('recommend'));
    }

    public function test_invalid_value_is_rejected(): void
    {
        $this->expectException(ValueError::class);

        Recommendation::from('maybe');
    }
}
