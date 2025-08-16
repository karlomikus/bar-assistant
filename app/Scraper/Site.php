<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

interface Site
{
    public function name(): string;

    public function description(): ?string;

    public function source(): ?string;

    public function instructions(): ?string;

    /**
     * @return array<string>
     */
    public function tags(): array;

    public function glass(): ?string;

    /**
     * @return array<mixed>
     */
    public function ingredients(): array;

    public function garnish(): ?string;

    /**
     * @return null|array<string, string>
     */
    public function image(): ?array;
}
