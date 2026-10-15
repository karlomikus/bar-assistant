<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Tests\TestCase;
use Illuminate\Contracts\Translation\Translator;
use Kami\Cocktail\Rules\NoReservedFilterDelimiter;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoReservedFilterDelimiterTest extends TestCase
{
    public function test_validation_passes_without_delimiter(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->createStub(Translator::class);

            return new PotentiallyTranslatedString('', $translator);
        };

        $rule = new NoReservedFilterDelimiter();
        $rule->validate('author', 'Jerry Thomas', $failed);

        $this->assertFalse($hasFailed);
    }

    public function test_validation_passes_with_commas(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->createStub(Translator::class);

            return new PotentiallyTranslatedString('', $translator);
        };

        $rule = new NoReservedFilterDelimiter();
        $rule->validate('origin_bar', 'American Bar, London', $failed);

        $this->assertFalse($hasFailed);
    }

    public function test_validation_passes_for_non_string_values(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->createStub(Translator::class);

            return new PotentiallyTranslatedString('', $translator);
        };

        $rule = new NoReservedFilterDelimiter();
        $rule->validate('author', null, $failed);

        $this->assertFalse($hasFailed);
    }

    public function test_validation_fails_with_delimiter(): void
    {
        $hasFailed = false;
        $failed = function () use (&$hasFailed): PotentiallyTranslatedString {
            $hasFailed = true;

            $translator = $this->createStub(Translator::class);

            return new PotentiallyTranslatedString('', $translator);
        };

        $rule = new NoReservedFilterDelimiter();
        $rule->validate('author', 'Jerry|Thomas', $failed);

        $this->assertTrue($hasFailed);
    }
}
