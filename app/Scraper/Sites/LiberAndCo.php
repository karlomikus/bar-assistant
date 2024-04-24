<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class LiberAndCo extends AbstractSiteExtractor
{
    public static function getSupportedUrls(): array
    {
        return [
            'https://www.liberandcompany.com',
        ];
    }

    public function name(): string
    {
        return $this->crawler->filter('.product .product_name')->first()->text();
    }

    public function description(): ?string
    {
        return $this->crawler->filter('.product .description p:first-of-type')->text();
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        $result = '';

        $this->crawler->filterXPath('//p/strong[contains(text(), \'Instructions:\')]/following::ul')->first()->filter('li')->each(function ($node, $i) use (&$result) {
            $result .= ($i + 1) . ". " . trim($node->text()) . "\n";
        });

        return trim($result);
    }

    public function garnish(): ?string
    {
        try {
            return $this->crawler->filterXPath('//p/strong[contains(text(), \'Garnish:\')]/following::ul')->first()->text();
        } catch (Throwable) {
            return null;
        }
    }

    public function glass(): ?string
    {
        try {
            return $this->crawler->filterXPath('//p/strong[contains(text(), \'Glass:\')]/following::ul')->first()->text();
        } catch (Throwable) {
            return null;
        }
    }

    public function tags(): array
    {
        return [];
    }

    public function ingredients(): array
    {
        $result = [];

        $this->crawler->filterXPath('//p/strong[contains(text(), \'Ingredients:\')]/following::ul')->first()->filter('li')->each(function ($node) use (&$result) {
            $ingredientString = $node->text();

            $recipeIngredient = $this->ingredientParser->parseLine($ingredientString, Units::Ml, [Units::Dash]);

            $amount = $recipeIngredient->amount;
            if ($amount === 0.0) {
                $amount++;
            }

            $result[] = new RecipeIngredient(
                ucfirst($recipeIngredient->name),
                $amount,
                $recipeIngredient->units,
                $recipeIngredient->source,
                $recipeIngredient->originalAmount,
                $recipeIngredient->comment,
                $recipeIngredient->amountMax
            );
        });

        return $result;
    }

    public function image(): ?array
    {
        $imageAuthor = null;
        $imageUrl = null;

        try {
            $imageAuthor = ' | ' . $this->crawler->filterXPath('//p/strong[contains(text(), \'Photo:\')]/following::*')->first()->text();
        } catch (Throwable) {
        }

        try {
            $imageUrl = trim($this->crawler->filter('.product .product__images img')->first()->attr('data-src'));
            if (str_starts_with($imageUrl, '//')) {
                $imageUrl = 'https:' . $imageUrl;
            }
        } catch (Throwable) {
        }

        return [
            'url' => $imageUrl,
            'copyright' => 'LiberAndCo' . $imageAuthor,
        ];
    }
}
