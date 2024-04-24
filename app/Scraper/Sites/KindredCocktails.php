<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Kami\RecipeUtils\UnitConverter\Units;
use Symfony\Component\DomCrawler\Crawler;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class KindredCocktails extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://kindredcocktails.com',
        ];
    }

    public function name(): string
    {
        return $this->getBaseContainer()->filter('h1')->first()->text();
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = $this->getBaseContainer()->filterXPath('//*[@property="schema:recipeInstructions"]')->first()->text();

        return trim($result);
    }

    public function garnish(): ?string
    {
        try {
            $asGarnish = $this->getBaseContainer()->filterXPath('//*[contains(text(), "(as garnish)")]')->first()->text();

            return trim(str_replace('(as garnish)', '', $asGarnish));
        } catch (Throwable) {
            return null;
        }
    }

    public function ingredients(): array
    {
        $result = [];

        $this->getBaseContainer()->filterXPath('//*[@property="schema:recipeIngredient"]')->each(function ($node) use (&$result) {
            $ingredientString = $node->text();

            if (!str_contains($ingredientString, 'as garnish')) {
                $result[] = $this->ingredientParser->parseLine($ingredientString, Units::Ml, [Units::Dash]);
            }
        });

        return $result;
    }

    public function image(): ?array
    {
        $result = [
            'url' => null,
            'copyright' => null,
        ];

        try {
            $result['url'] = $this->crawler->filterXPath('//img[@property="schema:image"]')->first()->attr('src');
            if (!str_starts_with($result['url'], 'http')) {
                $result['url'] = 'https://kindredcocktails.com' . $result['url'];
            }
        } catch (Throwable) {
        }

        try {
            $result['copyright'] = trim($this->crawler->filter('figcaption.caption')->first()->text());
        } catch (Throwable) {
        }

        return $result;
    }

    private function getBaseContainer(): Crawler
    {
        return $this->crawler->filterXPath('//*[@typeof="schema:Recipe"]');
    }
}
