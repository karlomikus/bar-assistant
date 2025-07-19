<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Exception;
use Throwable;
use Symfony\Component\DomCrawler\Crawler;
use Kami\Cocktail\Scraper\AbstractSite;

class KindredCocktails extends AbstractSite
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
                $result[] = $this->ingredientParser->parseLine($ingredientString);
            }
        });

        return $result;
    }

    public function image(): ?array
    {
        $result = [
            'uri' => null,
            'copyright' => null,
        ];

        try {
            $result['uri'] = $this->crawler->filterXPath('//img[@property="schema:image"]')->first()->attr('src');

            if (blank($result['uri'])) {
                throw new Exception('No image found');
            }

            if (!str_starts_with($result['uri'], 'http')) {
                $result['uri'] = 'https://kindredcocktails.com' . $result['uri'];
            }
        } catch (Throwable) {
        }

        try {
            $result['copyright'] = trim($this->crawler->filter('figcaption.caption')->first()->text());

            if (blank($result['copyright'])) {
                throw new Exception('No copyright found');
            }
        } catch (Throwable) {
        }

        return $result;
    }

    private function getBaseContainer(): Crawler
    {
        return $this->crawler->filterXPath('//*[@typeof="schema:Recipe"]');
    }
}
