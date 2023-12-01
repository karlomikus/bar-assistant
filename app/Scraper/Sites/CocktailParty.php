<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class CocktailParty extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://cocktailpartyapp.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filter('.recipe-card h1')->first()->text();
    }

    public function description(): ?string
    {
        return $this->crawler->filter('.recipe-card .recipe-description')->first()->text();
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        return $this->crawler->filter('.recipe-card .recipe-instructions')->first()->text();
    }

    public function tags(): array
    {
        return [];
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filter('.recipe-card .recipe-ingredients li')->each(function ($node) use (&$result) {
            $amount = $node->filter('.amount')->text();
            $ingredient = $node->filter('.ingredient')->text();

            // TODO: Handle parts units
            $recipeIngredient = $this->ingredientParser->parse($amount);

            $result[] = [
                'amount' => $recipeIngredient->amount,
                'units' => $recipeIngredient->units,
                'name' => $ingredient,
                'optional' => false,
            ];
        });

        return $result;
    }

    public function image(): ?array
    {
        return [
            'url' => $this->crawler->filter('.recipe-card .recipe-thumb img')->first()->attr('src'),
            'copyright' => 'CocktailParty',
        ];
    }
}
