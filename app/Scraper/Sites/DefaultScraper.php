<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Scraper\Concerns\ReadsLDJson;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;
use Kami\Cocktail\Scraper\Concerns\ReadsHTMLSchema;

class DefaultScraper extends AbstractSiteExtractor
{
    use ReadsLDJson, ReadsHTMLSchema;

    private array $ldSchema = [];
    private array $htmlSchema = [];

    public function __construct(string $url)
    {
        parent::__construct($url);

        $this->ldSchema = $this->readJSON();
        $this->htmlSchema = $this->readHTML();
    }

    public static function getSupportedUrls(): array
    {
        return [];
    }

    public function name(): string
    {
        $name = $this->ldSchema['name'] ?? null;

        if (!$name) {
            $name = $this->htmlSchema['name'] ?? null;
        }

        try {
            if (!$name) {
                $name = $this->crawler->filter('meta[property="og:title"]')->first()->attr('content') ?? null;
            }

            if (!$name) {
                $name = $this->crawler->filter('title')->text();
            }
        } catch (Throwable) {
        }

        if (!$name) {
            $name = '';
        }

        return trim($name);
    }

    public function description(): ?string
    {
        $description = $this->ldSchema['description'] ?? null;

        if (!$description) {
            $description = $this->htmlSchema['description'] ?? null;
        }

        try {
            if (!$description) {
                $description = $this->crawler->filter('meta[property="og:description"]')->first()->attr('content') ?? null;
            }
        } catch (Throwable) {
        }

        return $description;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        // Try with the parsed objects first
        $instructions = $this->ldSchema['recipeInstructions'] ?? [];
        $instructions = array_map(function ($instructionStep) {
            if (is_string($instructionStep)) {
                return $instructionStep;
            }

            if (isset($instructionStep['text'])) {
                return $instructionStep['text'];
            }
        }, $instructions);

        // Try manually
        if (empty($instructions)) {
            $fallback = $this->crawler->filterXPath('//*[@itemprop="recipeInstructions"]');
            if ($fallback->count() > 0) {
                $instructions = [$fallback->text()];
            }
        }

        $i = 1;
        $result = "";
        foreach ($instructions as $step) {
            $result .= $i . ". " . trim($step) . "\n";
            $i++;
        }

        return trim($result);
    }

    public function tags(): array
    {
        $sourceTags = $this->ldSchema['recipeCategory'] ?? [];
        if (!$sourceTags) {
            $sourceTags = $this->htmlSchema['recipeCategory'] ?? [];
        }

        if (!$sourceTags) {
            $sourceTags = $this->ldSchema['keywords'] ?? [];
        }

        if (!$sourceTags) {
            $sourceTags = $this->htmlSchema['keywords'] ?? [];
        }

        if (is_string($sourceTags)) {
            return [$sourceTags];
        }

        return $sourceTags;
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        $result = [];

        // Try "recipeIngredient" schema item
        $ingredients = $this->ldSchema['recipeIngredient'] ?? [];

        // Try "ingredients" schema item
        if (empty($ingredients)) {
            $ingredients = $this->ldSchema['ingredients'] ?? [];
        }

        // Try microdata directly from html
        if (empty($ingredients)) {
            try {
                $this->crawler->filterXPath('//*[@itemprop="recipeIngredient"]')->each(function ($node) use (&$ingredients) {
                    $ingredients[] = $node->text();
                });
            } catch (Throwable) {
            }
        }

        foreach ($ingredients as $ingredient) {
            $ingredient = html_entity_decode($ingredient, ENT_SUBSTITUTE | ENT_HTML5); // Convert entities to correct chars
            $ingredient = e(trim($ingredient, " \n\r\t\v\x00\"\'"));
            $recipeIngredient = $this->ingredientParser->parseLine($ingredient, Units::Ml);

            if (empty($recipeIngredient->amount) || empty($recipeIngredient->name) || empty($recipeIngredient->units)) {
                continue;
            }

            $result[] = new RecipeIngredient(
                ucfirst(e($recipeIngredient->name)),
                $recipeIngredient->amount,
                $recipeIngredient->units,
                $recipeIngredient->source,
                $recipeIngredient->originalAmount,
                $recipeIngredient->comment,
                $recipeIngredient->amountMax
            );
        }

        return $result;
    }

    public function image(): ?array
    {
        $images = $this->ldSchema['image'] ?? null;
        if (!$images) {
            $images = $this->htmlSchema['image'] ?? null;
        }

        if (is_array($images)) {
            $image = end($images);
        } else if ($images === null) {
            $image = null;
        } else {
            $image = $images;
        }

        $copyright = $this->getCopyrightHolder();

        return [
            'url' => $image,
            'copyright' => $copyright,
        ];
    }

    private function getCopyrightHolder(): ?string
    {
        if (count($this->ldSchema) > 0) {
            if (is_string($this->ldSchema['author'] ?? null)) {
                return $this->ldSchema['author'];
            }

            return $this->ldSchema['author']['name'] ?? null;
        }

        return null;
    }
}
