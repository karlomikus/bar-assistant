<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
use Kami\Cocktail\Scraper\HasJsonLd;
use Kami\Cocktail\Scraper\IngredientParser;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class TheDrinkBlog extends AbstractSiteExtractor
{
    use HasJsonLd;

    public static function getSupportedUrls(): array
    {
        return [
            'https://thedrinkblog.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filter('[itemprop="name"]')->text();
    }

    public function description(): ?string
    {
        return $this->getTypeFromSchema('WebPage')['description'];
    }

    public function source(): ?string
    {
        return $this->getTypeFromSchema('WebPage')['url'];
    }

    public function instructions(): ?string
    {
        $result = '';

        $i = 1;
        $this->crawler->filter('[itemprop="recipeInstructions"]')->each(function ($step) use (&$result, &$i) {
            $result .= $i . '. ' . $step->text() . "\n\n";
            $i++;
        });

        return $result;
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        $result = null;

        $this->crawler->filter('[itemprop="ingredients"]')->each(function ($ingredient) use (&$result) {
            $glassSentence = $ingredient->text();

            if (!str_contains(strtolower($glassSentence), 'glass type:')) {
                return;
            }

            $result = ucfirst(trim(str_replace('Glass type:', '', $glassSentence)));
        });

        return $result;
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filter('[itemprop="ingredients"]')->each(function ($ingredient) use (&$result) {
            $parsedIngredient = (new IngredientParser($ingredient->text()))->parse();

            ['amount' => $amount, 'units' => $units] = Utils::parseIngredientAmount($parsedIngredient['amount'] . ' ' . $parsedIngredient['units']);

            if ($amount === 0) {
                return;
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $parsedIngredient['name'],
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
        $imageObject = $this->getTypeFromSchema('ImageObject');

        return [
            'url' => $imageObject['url'],
            'copyright' => 'The Drink Blog',
        ];
    }

    private function getTypeFromSchema(string $type): ?array
    {
        $schema = $this->getSchema();
        foreach ($schema['@graph'] as $node) {
            if ($node['@type'] === $type) {
                return $node;
            }
        }

        return null;
    }
}
