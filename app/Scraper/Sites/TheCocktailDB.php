<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\Cocktail\Utils;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class TheCocktailDB extends AbstractSiteExtractor
{
    private array $apiDrinkData = [];

    public function __construct(string $url)
    {
        parent::__construct($url);

        if (!str_starts_with($url, 'https://www.thecocktaildb.com/api')) {
            $url = rtrim($url, '/');
            $urlParts = explode('/', $url);
            $drinkUri = end($urlParts);
            if (str_contains($drinkUri, '?c=')) {
                $drinkParts = explode('=', $drinkUri);
                $drinkApiId = $drinkParts[1];
            } else {
                $drinkParts = explode('-', $drinkUri);
                $drinkApiId = $drinkParts[0];
            }
            $url = 'https://www.thecocktaildb.com/api/json/v1/1/lookup.php?i=' . $drinkApiId;
        }

        $data = json_decode(file_get_contents($url), true);
        $this->apiDrinkData = $data['drinks'][0];
    }

    public static function getSupportedUrls(): array
    {
        return [
            'https://www.thecocktaildb.com',
        ];
    }

    public function name(): string
    {
        return $this->apiDrinkData['strDrink'];
    }

    public function description(): ?string
    {
        return null;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        return $this->apiDrinkData['strInstructions'];
    }

    public function tags(): array
    {
        return explode(',', $this->apiDrinkData['strTags'] ?? '');
    }

    public function glass(): ?string
    {
        return $this->apiDrinkData['strGlass'];
    }

    public function ingredients(): array
    {
        $result = [];

        foreach (range(1, 15) as $ingredientIndex) {
            $ingKey = 'strIngredient' . $ingredientIndex;
            $measureKey = 'strMeasure' . $ingredientIndex;

            if ($this->apiDrinkData[$ingKey] === null) {
                break;
            }

            ['amount' => $amount, 'units' => $units] = Utils::parseIngredientAmount($this->apiDrinkData[$measureKey]);

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $this->apiDrinkData[$ingKey],
                'optional' => false,
            ];
        }

        return $result;
    }

    public function image(): ?array
    {
        return [
            'url' => $this->apiDrinkData['strImageSource'],
            'copyright' => $this->apiDrinkData['strImageAttribution'],
        ];
    }
}
