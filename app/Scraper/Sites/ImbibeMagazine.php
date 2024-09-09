<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\UnitConverter\Units;

class ImbibeMagazine extends DefaultScraper
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://imbibemagazine.com',
        ];
    }

    public function name(): string
    {
        return $this->getRecipeSchema()['name'] ?? parent::name();
    }

    public function description(): ?string
    {
        $desc = $this->getRecipeSchema()['articleBody'] ?? parent::description();

        return trim($desc);
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';
        $i = 1;
        foreach ($this->getRecipeSchema()['recipeInstructions'] ?? [] as $step) {
            $result .= $i . '. ' . trim($step['text']) . "\n";
            $i++;
        }

        if ($result === '') {
            try {
                $result = $this->crawler->filterXPath('//div[contains(@class, \'recipe__main-content\')]')->first()->filterXPath('//p[3]')->text();
            } catch (\Throwable) {
            }
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

        if ($result === null) {
            foreach ($this->getLegacyRecipeIngredients() as $line) {
                if (str_starts_with($line, 'Glass:')) {
                    $result = str_replace('Glass:', '', $line);
                }
            }
        }

        return $result;
    }

    public function ingredients(): array
    {
        $result = [];

        $schemaIngredients = $this->getRecipeSchema()['recipeIngredient'] ?? [];

        foreach ($schemaIngredients as $ingredient) {
            $result[] = $this->ingredientParser->parseLine($ingredient['ingredient'], $this->defaultConvertTo, [Units::Dash]);
        }

        if (empty($result)) {
            foreach ($this->getLegacyRecipeIngredients() as $line) {
                if (str_starts_with($line, 'Tools:') || str_starts_with($line, 'Garnish:') || str_starts_with($line, 'Glass:')) {
                    continue;
                }
                $result[] = $this->ingredientParser->parseLine($line, $this->defaultConvertTo, [Units::Dash]);
            }
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

        if ($result === null) {
            foreach ($this->getLegacyRecipeIngredients() as $line) {
                if (str_starts_with($line, 'Garnish:')) {
                    $result = str_replace('Garnish:', '', $line);
                }
            }
        }

        return $result;
    }

    public function image(): ?array
    {
        $schema = $this->getRecipeSchema();

        $url = null;
        $copyright = 'Imbibe Magazine';

        $imageCredit = $this->crawler->filter('.recipe__image-credit');
        if ($imageCredit->count() > 0) {
            $copyright .= ' | ' . str_replace('Photo: ', '', $imageCredit->text(''));
        }

        if (!empty($schema)) {
            $url = $schema['image'] ?? null;
        } else {
            $url = explode(' ', $this->crawler->filter('img.recipe__image')->first()->attr('data-srcset'))[0];
        }

        return [
            'url' => $url,
            'copyright' => $copyright,
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

    private function getLegacyRecipeIngredients(): array
    {
        $result = [];

        try {
            $ingredientsParagraph = $this->crawler->filterXPath('//div[contains(@class, \'recipe__main-content\')]')->first()->filterXPath('//p[2]')->html();
            $ingredientsParagraphLines = explode('<br>', $ingredientsParagraph);
            foreach ($ingredientsParagraphLines as $line) {
                $result[] = trim($line);
            }
        } catch (\Throwable) {
        }

        return $result;
    }
}
