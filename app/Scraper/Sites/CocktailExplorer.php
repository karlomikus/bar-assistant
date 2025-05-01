<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\AmountValue;
use Kami\RecipeUtils\RecipeIngredient;
use Symfony\Component\DomCrawler\Crawler;

class CocktailExplorer extends DefaultScraper
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://www.cocktailexplorer.co',
        ];
    }

    public function ingredients(): array
    {
        $result = $this->crawler->filter('.video__recipe__ingredients li')->each(function (Crawler $node): RecipeIngredient {
            $amount = $node->filter('.video__recipe__ingredient__quantity')->attr('data-ingredient-amount');
            $unit = $node->filter('.video__recipe__ingredient__quantity')->attr('data-ingredient-unit');
            $ingredient = $node->filter('strong')->text();

            return new RecipeIngredient(
                name: $ingredient,
                amount: AmountValue::fromString($amount),
                units: $unit,
                source: $node->text(),
            );
        });

        return $result;
    }
}
