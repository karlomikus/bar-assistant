<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\UnitConverter\Units;
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
        $this->crawler->filterXPath("//ol[contains(@class, 'recipe_recipeIngredients__')]")->filter('li')->each(function ($node) use (&$result) {
            $result[] = $this->ingredientParser->parseLine($node->text(), $this->defaultConvertTo, [Units::Dash, Units::Barspoon]);
        });

        return $result;
    }

    public function image(): ?array
    {
        $style = $this->crawler->filterXPath("//div[contains(@class, 'recipe_recipeImage__')]")->attr('style');
        preg_match_all('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', $style, $matches);

        return [
            'uri' => $this->getSupportedUrls()[0] . $matches['image'][0],
            'copyright' => 'Eric\'s Cocktail Guide',
        ];
    }
}
