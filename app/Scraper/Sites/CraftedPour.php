<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\AmountValue;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\Cocktail\Scraper\AbstractSite;

class CraftedPour extends AbstractSite
{
    /** @var array<string, mixed> */
    private readonly array $dataRecipeProp;

    public function __construct(string $url, string $content = '')
    {
        parent::__construct($url, $content);

        $rawDataStructure = $this->crawler->filter('#__NEXT_DATA__')->first()->text();
        $data = json_decode($rawDataStructure, true);

        $this->dataRecipeProp = $data['props']['pageProps']['recipe'] ?? [];
    }

    public static function getSupportedUrls(): array
    {
        return [
            'https://craftedpour.com',
        ];
    }

    public function name(): string
    {
        return $this->dataRecipeProp['name'] ?? '';
    }

    public function description(): ?string
    {
        return $this->dataRecipeProp['desc_long'] ?? '';
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        return $this->dataRecipeProp['instructions'] ?? '';
    }

    public function tags(): array
    {
        return $this->dataRecipeProp['tags'] ?? [];
    }

    public function glass(): ?string
    {
        return $this->dataRecipeProp['glass'] ?? null;
    }

    public function garnish(): ?string
    {
        return $this->dataRecipeProp['garnish'] ?? null;
    }

    public function method(): ?string
    {
        return $this->dataRecipeProp['build'] ?? null;
    }

    public function ingredients(): array
    {
        $result = [];

        foreach ($this->dataRecipeProp['ingredients'] ?? [] as $ingredient) {
            $result[] = new RecipeIngredient(
                $ingredient['ingredient'],
                AmountValue::fromString($ingredient['quantity']),
                $ingredient['unit'] ?? '',
                null,
                $ingredient['link'],
            );
        }

        return $result;
    }

    public function image(): ?array
    {
        return [
            'uri' => '',
            'copyright' => trim('Crafted Pour ' . $this->dataRecipeProp['creator_name']),
        ];
    }
}
