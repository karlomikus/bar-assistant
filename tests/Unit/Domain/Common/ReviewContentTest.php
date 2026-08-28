<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Exception\DomainException;

final class ReviewContentTest extends TestCase
{
    public function test_create_trims_surrounding_whitespace(): void
    {
        $content = ReviewContent::create('  Hello world  ');

        $this->assertSame('Hello world', $content->value);
    }

    public function test_create_accepts_5000_characters(): void
    {
        $text = str_repeat('a', 5000);
        $content = ReviewContent::create($text);

        $this->assertSame($text, $content->value);
    }

    public function test_create_rejects_empty_string(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Review content must not be empty');

        ReviewContent::create('');
    }

    public function test_create_rejects_whitespace_only(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Review content must not be empty');

        ReviewContent::create("   \n\t  ");
    }

    public function test_create_rejects_content_over_5000_characters(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Review content must not exceed 5000 characters');

        ReviewContent::create(str_repeat('a', 5001));
    }

    public function test_create_rejects_content_exceeding_limit_after_trim(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Review content must not exceed 5000 characters');

        // 5000 chars of content + surrounding whitespace would exceed the cap once trimmed.
        ReviewContent::create(' ' . str_repeat('a', 5001) . ' ');
    }
}
