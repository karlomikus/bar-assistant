<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;

class DiffordsGuide extends DefaultScraper
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://www.diffordsguide.com',
        ];
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        try {
            return $this->crawler->filterXPath('//h3[contains(text(), \'Serve in a\')]/../a')->text();
        } catch (Throwable) {
            return null;
        }
    }

    public function garnish(): ?string
    {
        try {
            return $this->crawler->filterXPath('//h3[contains(text(), \'Garnish:\')]/../p')->text();
        } catch (Throwable) {
            return null;
        }
    }
}
