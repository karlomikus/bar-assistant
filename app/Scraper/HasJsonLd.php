<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

trait HasJsonLd
{
    public function parseSchema(): ?array
    {
        $jsonLdSchema = $this->crawler->filterXPath('//script[@type="application/ld+json"]')->first()->text();

        return json_decode($jsonLdSchema, true);
    }
}
