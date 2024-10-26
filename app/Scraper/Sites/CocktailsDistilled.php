<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\RecipeIngredient;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class CocktailsDistilled extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://cocktailsdistilled.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filter('.recipe-wrap h1')->text('');
    }

    public function description(): ?string
    {
        return $this->crawler->filter('.recipe-wrap .intro')->text('');
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function glass(): ?string
    {
        return $this->crawler->filterXPath('//h2[contains(text(), \'Glass\')]/../p')->text('');
    }

    public function garnish(): ?string
    {
        return $this->crawler->filter('.directions')->first()->filterXPath('//h2[contains(text(), \'Garnish\')]/following-sibling::p')->text('');
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filter('.ingredients ul li')->each(function ($listNode) use (&$result) {
            $ingredientName = $listNode->innerText();
            if (!isset($ingredientName) || (string) $ingredientName === '') {
                $ingredientName = $listNode->filter('a')->text();
            }

            $measureMl = $listNode->filter('.measure.ml')->text();
            if (str_contains($measureMl, 'ml')) {
                $amount = (int) str_replace('ml', '', $measureMl);
                $units = 'ml';
            } else {
                $recipeIngredient = $this->ingredientParser->parseLine($measureMl, $this->defaultConvertTo, [Units::Dash]);
                $amount = $recipeIngredient->amount;
                $units = $recipeIngredient->units;
            }

            $result[] = new RecipeIngredient(
                $ingredientName,
                (float) $amount,
                $units,
                $listNode->innerText()
            );
        });

        return $result;
    }

    public function instructions(): ?string
    {
        return $this->crawler->filter('.directions')->first()->filterXPath('//h2[contains(text(), \'Instructions\')]/../p')->text('');
    }

    public function method(): ?string
    {
        $method = $this->crawler->filter('.recipe-main .traits div:last-child span')->text('');

        return match ($method) {
            'Stirred' => 'Stir',
            'Blended' => 'Blend',
            'Built' => 'Build',
            'Dry Shake' => 'Shake',
            'Muddled' => 'Muddle',
            'Shaken' => 'Shake',
            'Stirred' => 'Stir',
            'Swizzle' => 'Blend',
            'Thrown' => null,
            'Whisk' => null,
            '' => null,
            default => null
        };
    }

    public function image(): ?array
    {
        $image = $this->crawler->filter('.recipe-slideshow-images img')->first()->attr('src');

        return [
            'uri' => $image,
            'copyright' => 'Cocktails Distilled',
        ];
    }
}
