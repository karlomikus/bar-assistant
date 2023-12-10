<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class SteveTheBartender extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://stevethebartender.com',
        ];
    }

    public function name(): string
    {
        return $this->getRecipeSchema()['name'];
    }

    public function instructions(): ?string
    {
        $instructions = $this->getRecipeSchema()['recipeInstructions'];
        $i = 1;
        $result = '';
        foreach ($instructions as $step) {
            $result .= $i . ". " . trim($step['text']) . "\n";
            $i++;
        }

        return trim($result);
    }

    public function image(): ?array
    {
        return [
            'url' => $this->getRecipeSchema()['image'][0],
            'copyright' => 'Steve the Bartender',
        ];
    }

    public function ingredients(): array
    {
        $result = [];

        $ingredients = $this->getRecipeSchema()['recipeIngredient'];

        foreach ($ingredients as $ingredient) {
            $result[] = $this->ingredientParser->parseLine($ingredient, Units::Ml);
        }

        return $result;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function description(): ?string
    {
        return trim($this->getRecipeSchema()['description']);
    }

    private function getRecipeSchema(): array
    {
        $recipeSchema = [];

        $this->crawler->filterXPath('//script[@type="application/ld+json"]')->each(function ($node) use (&$recipeSchema) {
            $parsedSchema = json_decode($node->text(), true);
            foreach ($parsedSchema['@graph'] as $graph) {
                if (array_key_exists('@type', $graph) && $graph['@type'] === 'Recipe') {
                    $recipeSchema = $graph;
                }
            }
        });

        return $recipeSchema;
    }
}
