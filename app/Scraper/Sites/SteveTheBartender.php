<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Scraper\AbstractSite;

class SteveTheBartender extends AbstractSite
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
            $result .= $i . ". " . trim((string) $step['text']) . "\n";
            $i++;
        }

        return trim($result);
    }

    public function image(): ?array
    {
        return [
            'uri' => $this->getRecipeSchema()['image'][0],
            'copyright' => 'Steve the Bartender',
        ];
    }

    public function ingredients(): array
    {
        $result = [];

        $ingredients = $this->getRecipeSchema()['recipeIngredient'];

        foreach ($ingredients as $ingredient) {
            $result[] = $this->ingredientParser->parseLine($ingredient);
        }

        return $result;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function description(): ?string
    {
        return trim((string) $this->getRecipeSchema()['description']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRecipeSchema(): array
    {
        $recipeSchema = [];

        $this->crawler->filterXPath('//script[@type="application/ld+json"]')->each(function ($node) use (&$recipeSchema) {
            $parsedSchema = json_decode((string) $node->text(), true);
            foreach ($parsedSchema['@graph'] as $graph) {
                if (array_key_exists('@type', $graph) && $graph['@type'] === 'Recipe') {
                    $recipeSchema = $graph;
                }
            }
        });

        return $recipeSchema;
    }
}
