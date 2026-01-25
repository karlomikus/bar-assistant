<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

class MakeMeACocktail extends DefaultScraper
{
    #[\Override]
    public static function getSupportedUrls(): array
    {
        return [
            'https://makemeacocktail.com',
        ];
    }

    #[\Override]
    public function tags(): array
    {
        $result = [];

        $this->crawler->filterXPath('//div[contains(text(), "Details")]/following::div[2]/div/div')->each(function ($node) use (&$result) {
            $result[] = trim((string) $node->text());
        });

        return $result;
    }
}
