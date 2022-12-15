<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
use Kami\Cocktail\Scraper\HasJsonLd;
use Kami\Cocktail\Scraper\ScraperInfoContract;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class Imbibe extends AbstractSiteExtractor implements ScraperInfoContract
{
    use HasJsonLd;

    public static function getSupportedUrls(): array
    {
        return [
            'https://imbibemagazine.com',
        ];
    }

    public function name(): string
    {
        return str_replace(' - Imbibe Magazine', '', $this->getTypeFromSchema('WebPage')['name']);
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
        $jsonLdSchema = $this->crawler->filterXPath('//script[@type="application/ld+json"]')->last()->text();
        $recipeSchema = json_decode($jsonLdSchema, true);

        $result = '';
        $i = 1;
        foreach ($recipeSchema['recipeInstructions'] as $step) {
            $result .= $i . '. ' . $step['text'] . "\n\n";
        }

        return $result;
    }

    public function tags(): array
    {
        return [];
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

        $jsonLdSchema = $this->crawler->filterXPath('//script[@type="application/ld+json"]')->last()->text();
        $recipeSchema = json_decode($jsonLdSchema, true);

        foreach ($recipeSchema['recipeIngredient'] as $ingredient) {
            $ingredient = $ingredient['ingredient'];

            $amount = 0;
            $units = '';
            $name = $ingredient;

            if (str_contains(strtolower($ingredient), 'oz.')) {
                $splitByOunce = explode('oz.', $ingredient);

                ['amount' => $amount, 'units' => $units] = Utils::parseIngredientAmount($splitByOunce[0] . ' oz');
                $name = trim($splitByOunce[1]);
            } else {
                continue;
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $name,
                'optional' => false,
            ];
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
        $imageObject = $this->getTypeFromSchema('ImageObject');

        return [
            'url' => $imageObject['contentUrl'],
            'copyright' => $imageObject['caption'],
        ];
    }

    public function getInfoMessage(): string
    {
        return 'This scraper currently imports only ingredients with "ounce" units, the rest are skipped!';
    }

    private function getTypeFromSchema(string $type): ?array
    {
        $schema = $this->parseSchema();
        foreach ($schema['@graph'] as $node) {
            if ($node['@type'] === $type) {
                return $node;
            }
        }

        return null;
    }
}
