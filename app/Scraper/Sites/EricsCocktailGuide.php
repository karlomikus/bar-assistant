<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class EricsCocktailGuide extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://www.ericscocktailguide.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filterXPath("//h1[contains(@class, 'recipe_recipeHeader__')]")->text();
    }

    public function description(): ?string
    {
        return null;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        return $this->crawler->filterXPath("//div[contains(@class, 'recipe_recipeInstructions__')]")->text();
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        $result = [];
        // TODO: Also possible to scrape substitutes
        $this->crawler->filterXPath("//ol[contains(@class, 'recipe_recipeIngredients__')]")->filter('li')->each(function ($node) use (&$result) {
            $amount = $node->filter('span')->first()->text();
            $units = trim($node->filterXPath('node()/text()')->text());
            $name = $node->filter('a')->text();

            ['amount' => $amount, 'units' => $units] = Utils::parseIngredientAmount($amount . ' ' . $units);

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => ucfirst($name),
                'optional' => false,
            ];
        });

        return $result;
    }

    public function garnish(): ?string
    {
        return null;
    }

    public function image(): ?array
    {
        $style = $this->crawler->filterXPath("//div[contains(@class, 'recipe_recipeImage__')]")->attr('style');
        preg_match_all('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', $style, $matches);

        return [
            'url' => $this->getSupportedUrls()[0] . $matches['image'][0],
            'copyright' => 'Eric\s Cocktail Guide',
        ];
    }
}
