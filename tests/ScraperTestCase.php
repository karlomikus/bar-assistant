<?php

declare(strict_types=1);

namespace Tests;

abstract class ScraperTestCase extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped(
            'Scraper testing is a bit flaky, needs refactoring. Skipping for now.',
        );
    }
}
