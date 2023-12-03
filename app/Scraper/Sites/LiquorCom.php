<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class LiquorCom extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://www.liquor.com',
        ];
    }

    public function name(): string
    {
        return $this->getRecipeSchema()['name'] ?? '';
    }

    public function instructions(): ?string
    {
        $result = '';
        $i = 1;

        foreach ($this->getRecipeSchema()['recipeInstructions'] as $instruction) {
            $result .= $i . '. ' . e(html_entity_decode($instruction['text'])) . "\n";
            $i++;
        }

        return $result;
    }

    public function description(): ?string
    {
        return e(html_entity_decode($this->getRecipeSchema()['description'] ?? ''));
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function ingredients(): array
    {
        $ingredients = [];

        foreach ($this->getRecipeSchema()['recipeIngredient'] as $sourceIngredient) {
            $recipeIngredient = $this->ingredientParser->parseWithUnits($sourceIngredient, Units::Ml);
            $ingredients[] = [
                'amount' => $recipeIngredient->amount,
                'units' => $recipeIngredient->units,
                'name' => e(html_entity_decode($recipeIngredient->name)),
                'optional' => false,
            ];
        }

        return $ingredients;
    }

    public function image(): ?array
    {
        return [
            'url' => $this->getRecipeSchema()['image']['url'] ?? '',
            'copyright' => $this->getRecipeSchema()['author']['name'] ?? 'Liquor.com',
        ];
    }

    private function getRecipeSchema(): array
    {
        $recipeSchema = [];

        $this->crawler->filterXPath('//script[@type="application/ld+json"]')->each(function ($node) use (&$recipeSchema) {
            $recipeSchema = json_decode($node->text(), true, JSON_INVALID_UTF8_SUBSTITUTE);
        });

        return $recipeSchema[0];
    }
}
