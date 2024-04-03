<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\RecipeIngredient;
use Symfony\Component\DomCrawler\Crawler;
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
        return $this->findDescriptionContainer()->filter('.elementor-widget-theme-post-title h1')->first()->text();
    }

    public function description(): ?string
    {
        return $this->joinParagraphs($this->findDescriptionContainer()->filter('.elementor-widget-theme-post-content div p'));
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        return $this->joinParagraphs($this->findRecipeContainer()->filter('.elementor-widget-text-editor p'));
    }

    public function tags(): array
    {
        return [];
    }

    public function ingredients(): array
    {
        return $this->findRecipeContainer()
            ->filter(".elementor-widget-shortcode li")
            ->each(function ($node): RecipeIngredient {
                $amount = $node->filter('.amount')->text();
                $ingredient = $node->filter('.ingredient')->text();
                $recipeIngredient = $this->ingredientParser->parseLine($amount);
                return new RecipeIngredient(
                    $ingredient,
                    $recipeIngredient->amount,
                    $recipeIngredient->units,
                    $recipeIngredient->source,
                    $recipeIngredient->originalAmount,
                    $recipeIngredient->comment,
                    $recipeIngredient->amountMax
                );
            });
    }

    public function image(): ?array
    {
        return [
            'url' => $this->findDescriptionContainer()->filter('.elementor-widget-image img')->first()->attr('src'),
            'copyright' => 'CocktailParty',
        ];
    }

    private function joinParagraphs(Crawler $nodes): string
    {
        $paragraphs = $nodes->each(function ($node): string {
            return $node->text();
        });

        return implode("\n\n", $paragraphs);
    }

    private function findDescriptionContainer(): Crawler
    {
        return $this->crawler
            ->filter('.elementor-widget-heading h1')
            ->closest('.elementor-element[data-element_type="container"]');
    }

    private function findRecipeContainer(): Crawler
    {
        return $this->crawler
            ->filter('.elementor-widget-shortcode li .ingredient')
            ->closest('.elementor-element[data-element_type="container"]');
    }
}
