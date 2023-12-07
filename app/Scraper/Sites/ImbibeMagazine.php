<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\UnitConverter\Units;
use Kami\RecipeUtils\UnitConverter\Converter;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class ImbibeMagazine extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://imbibemagazine.com',
        ];
    }

    public function name(): string
    {
        return $this->getRecipeSchema()['name'];
    }

    public function description(): ?string
    {
        return trim($this->getRecipeSchema()['articleBody']);
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';
        $i = 1;
        foreach ($this->getRecipeSchema()['recipeInstructions'] as $step) {
            $result .= $i . '. ' . trim($step['text']) . "\n";
            $i++;
        }

        return trim($result);
    }

    public function glass(): ?string
    {
        $result = null;

        $this->crawler->filter('.ingredients__tools li')->each(function ($listItem) use (&$result) {
            if (str_contains(strtolower($listItem->text()), 'glass')) {
                $result = $listItem->text();

                $result = ucfirst(str_replace('Glass:', '', $result));
            }
        });

        return $result;
    }

    public function ingredients(): array
    {
        $result = [];

        $schemaIngredients = $this->getRecipeSchema()['recipeIngredient'];

        foreach ($schemaIngredients as $ingredient) {
            $result[] = $this->ingredientParser->parseLine($ingredient['ingredient'], Units::Ml);
        }

        return $result;
    }

    public function garnish(): ?string
    {
        $result = null;

        $this->crawler->filter('.ingredients__tools li')->each(function ($listItem) use (&$result) {
            if (str_contains(strtolower($listItem->text()), 'garnish')) {
                $result = $listItem->text();

                $result = ucfirst(str_replace('Garnish:', '', $result));
            }
        });

        return $result;
    }

    public function image(): ?array
    {
        $schema = $this->getRecipeSchema();

        $extra = '';
        $imageCredit = $this->crawler->filter('.recipe__image-credit');
        if ($imageCredit->count() > 0) {
            $extra .= ' | ' . str_replace('Photo: ', '', $imageCredit->text(''));
        }

        return [
            'url' => $schema['image'],
            'copyright' => $schema['publisher'] . $extra,
        ];
    }

    private function getRecipeSchema(): array
    {
        $recipeSchema = [];

        $this->crawler->filterXPath('//script[@type="application/ld+json"]')->each(function ($node) use (&$recipeSchema) {
            $parsedSchema = json_decode($node->text(), true);
            if (array_key_exists('@type', $parsedSchema) && $parsedSchema['@type'] === 'Recipe') {
                $recipeSchema = $parsedSchema;
            }
        });

        return $recipeSchema;
    }
}
