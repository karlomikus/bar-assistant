<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Scraper\IngredientParser;
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
            if (!$ingredientName || (string) $ingredientName === '') {
                $ingredientName = $listNode->filter('a')->text();
            }

            $measureMl = $listNode->filter('.measure.ml')->text();
            if (str_contains($measureMl, 'ml')) {
                $amount = (int) str_replace('ml', '', $measureMl);
                $units = 'ml';
            } else {
                ['amount' => $amount, 'units' => $units] = (new IngredientParser($measureMl))->parse();
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $ingredientName,
                'optional' => false,
            ];
        });

        return $result;
    }

    public function instructions(): ?string
    {
        return $this->crawler->filter('.directions')->first()->filterXPath('//h2[contains(text(), \'Instructions\')]/../p')->text('');
    }

    public function method(): ?string
    {
        return $this->crawler->filter('.recipe-main .traits div:last-child span')->text('');
    }

    public function image(): ?array
    {
        $image = $this->crawler->filter('.recipe-slideshow-images img')->first()->attr('src');

        return [
            'url' => $image,
            'copyright' => 'Cocktails Distilled',
        ];
    }
}
