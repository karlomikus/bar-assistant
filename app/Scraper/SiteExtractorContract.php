<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

interface SiteExtractorContract
{
    public function name(): string;

    public function description(): ?string;

    public function source(): ?string;

    public function instructions(): ?string;

    public function tags(): array;

    public function glass(): ?string;

    public function ingredients(): array;

    public function garnish(): ?string;

    public function image(): ?array;
}
