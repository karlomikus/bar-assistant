<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
use Kami\Cocktail\Scraper\HasJsonLd;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class ACoupleCooks extends AbstractSiteExtractor
{
    use HasJsonLd;

    public static function getSupportedUrls(): array
    {
        return [
            'https://www.acouplecooks.com',
        ];
    }

    public function name(): string
    {
        return $this->getTypeFromSchema('Recipe')['name'];
    }

    public function description(): ?string
    {
        return $this->getTypeFromSchema('Recipe')['description'];
    }

    public function source(): ?string
    {
        return $this->getTypeFromSchema('Recipe')['url'];
    }

    public function instructions(): ?string
    {
        $result = '';
        $instructions = $this->getTypeFromSchema('Recipe')['recipeInstructions'];
        $i = 1;
        foreach ($instructions as $step) {
            $result .= $i . ". " . $step['text'] . "\n\n";
            $i++;
        }

        return $result;
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

        $this->crawler->filter('div.tasty-recipes-ingredients ul')->first()->filter('li')->each(function ($node) use (&$result) {
            $amount = 0;
            $units = '';
            $name = $node->text();

            if ($node->filter('span')->count() > 0) {
                $amount = $node->filter('span')->first()->attr('data-amount');
                $units = $node->filter('span')->first()->attr('data-unit');

                if ($units && ($units === 'ounce' || $units === 'ounces')) {
                    ['amount' => $amount, 'units' => $units] = Utils::parseIngredientAmount($amount . ' oz');
                }

                $name = explode($node->filter('span')->last()->text(), $node->text());
                $name = trim($name[1], " \n\r\t\v\x00\(\)");
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units ?? '',
                'name' => $name,
                'optional' => false,
            ];
        });

        return $result;
    }

    public function image(): ?array
    {
        return [
            'url' => $this->getTypeFromSchema('ImageObject')['url'],
            'copyright' => 'A Couple Cooks',
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
