<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Brick\Schema\Base;
use Brick\Schema\SchemaReader;
use Brick\Schema\Interfaces\Recipe;
use Kami\Cocktail\Scraper\IngredientParser;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class DefaultScraper extends AbstractSiteExtractor
{
    private ?Recipe $recipeSchema = null;

    public function __construct(string $url)
    {
        parent::__construct($url);

        $schemaReader = SchemaReader::forAllFormats();
        $things = $schemaReader->readHtml($this->crawler->html(), $url);

        foreach ($things as $thing) {
            if ($thing instanceof Recipe) {
                $this->recipeSchema = $thing;
            }
        }
    }

    public static function getSupportedUrls(): array
    {
        return [];
    }

    public function name(): string
    {
        $name = null;

        if ($this->recipeSchema) {
            $name = $this->recipeSchema->name?->getFirstValue() ?? null;
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

        return e(html_entity_decode(trim($name), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5));
    }

    public function description(): ?string
    {
        $description = null;

        if ($this->recipeSchema) {
            $description = $this->recipeSchema->description?->getFirstValue() ?? null;
        }

        return $description;
    }

    public function source(): ?string
    {
        return $this->url;
    }

    public function instructions(): ?string
    {
        if (!$this->recipeSchema) {
            return null;
        }

        // Try with the parsed objects first
        $instructions = $this->recipeSchema->recipeInstructions?->getValues() ?? [];
        $instructions = array_map(function ($node) {
            if (is_string($node)) {
                return $node;
            }

            if ($node->text) {
                return $node->text->toString();
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
        if ($this->recipeSchema) {
            $keywords = '';

            // Try with recipe category first
            if ($this->recipeSchema->recipeCategory?->count() ?? 0 > 0) {
                $keywords = implode(',', $this->recipeSchema->recipeCategory->getValues());
            }

            // Fallback to keywords
            if ($keywords === '') {
                $keywords = $this->recipeSchema->keywords?->toString();
            }

            return explode(',', $keywords);
        }

        return [];
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        if (!$this->recipeSchema) {
            return [];
        }

        $result = [];

        // Try "recipeIngredient" schema item
        $ingredients = $this->recipeSchema->recipeIngredient?->getValues() ?? [];

        // Try "ingredients" schema item
        if (empty($ingredients)) {
            $ingredients = $this->recipeSchema->ingredients?->getValues() ?? [];
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
            $ingredient = e(html_entity_decode($ingredient, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5)); // Convert entities to correct chars
            $ingredient = trim($ingredient, " \n\r\t\v\x00\"\'");
            ['amount' => $amount, 'units' => $units, 'name' => $name] = (new IngredientParser($ingredient))->parse();

            if (empty($amount) || empty($name) || empty($units)) {
                continue;
            }

            $result[] = [
                'amount' => $amount,
                'units' => $units,
                'name' => $name,
                'optional' => false,
            ];
        }

        return $result;
    }

    public function image(): ?array
    {
        $images = $this->recipeSchema?->image->getValues() ?? [];
        $mainImage = end($images);

        $image = '';

        if (is_string($mainImage)) {
            $image = $mainImage ?? null;
        }

        if ($mainImage instanceof Base && $mainImage->url) {
            $image = (string) $mainImage->url;
        }

        $copyright = $this->copyrightHolder();

        return [
            'url' => $image,
            'copyright' => $copyright,
        ];
    }

    private function copyrightHolder(): ?string
    {
        if ($this->recipeSchema) {
            if (is_string($this->recipeSchema->author->getFirstValue())) {
                return $this->recipeSchema->author->getFirstValue();
            }

            return $this->recipeSchema->author->getFirstValue()->name->toString();
        }

        return null;
    }
}
