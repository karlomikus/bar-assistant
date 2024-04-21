<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

class MakeMeACocktail extends DefaultScraper
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://makemeacocktail.com',
        ];
    }

    public function tags(): array
    {
        $result = [];

        $this->crawler->filterXPath('//div[contains(text(), "Details")]/following::div[2]/div/div')->each(function ($node) use (&$result) {
            $result[] = trim($node->text());
        });

        return $result;
    }
}
