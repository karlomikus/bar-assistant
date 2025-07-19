<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Kami\RecipeUtils\RecipeIngredient;
use Symfony\Component\DomCrawler\Crawler;
use Kami\Cocktail\Scraper\AbstractSite;

class CocktailParty extends AbstractSite
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
        return $this->joinParagraphs($this->crawler->filter('[data-widget_type="theme-post-content.default"] p'));
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
        return $this->findDescriptionContainer()->filter('.elementor-widget-post-info a')->each(function ($node): string {
            return $node->text();
        });
    }

    public function ingredients(): array
    {
        return $this->findRecipeContainer()
            ->filter(".elementor-widget-shortcode li")
            ->each(function ($node): RecipeIngredient {
                $amount = $node->filter('.amount')->text();
                $ingredient = $node->filter('.ingredient')->text();
                $recipeIngredient = $this->ingredientParser->parseLine($amount);
                $unit = match ($recipeIngredient->units) {
                    // Cocktail Party may sometimes use units that the ingredient parser doesn't understand.
                    // Because of that, something like "1 piece" will be parsed as having no unit and an ingredient
                    // named "piece." Since we know that the .amount element doesn't actually contain the ingredient,
                    // we can fall back to using the name of the ingredient as the unit if the parsed unit is empty.
                    '' => $recipeIngredient->name,
                    // Cocktail Party may say "parts," but they actually mean fluid ounces.
                    'part' => 'oz',
                    default => $recipeIngredient->units,
                };

                return new RecipeIngredient(
                    $ingredient,
                    $recipeIngredient->amount,
                    $unit,
                    $node->text(''),
                    $recipeIngredient->comment,
                    $recipeIngredient->amountMax
                );
            });
    }

    public function image(): ?array
    {
        return [
            'uri' => $this->findDescriptionContainer()->filter('.elementor-widget-image img')->first()->attr('src'),
            'copyright' => 'CocktailParty',
        ];
    }

    protected function cleanDescription(?string $description): ?string
    {
        // We clean up the description within joinParagraphs already, so we
        // do not need to do any further clean up here.
        return $description;
    }

    private function joinParagraphs(Crawler $nodes): string
    {
        $paragraphs = $nodes->each(function ($node): string {
            return $this->clean($node->text());
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
