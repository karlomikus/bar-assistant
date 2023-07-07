<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Brick\Schema\SchemaReader;
use Brick\Schema\Interfaces\Recipe;
use Brick\Schema\Interfaces\Article;
use Kami\Cocktail\Scraper\IngredientParser;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class DefaultScraper extends AbstractSiteExtractor
{
    private ?Recipe $recipeSchema = null;
    private ?Article $articleSchema = null;

    public function __construct(string $url)
    {
        parent::__construct($url);

        $schemaReader = SchemaReader::forAllFormats();
        $things = $schemaReader->readHtml($this->crawler->html(), $url);

        foreach ($things as $thing) {
            if ($thing instanceof Recipe) {
                $this->recipeSchema = $thing;
            }

            if ($thing instanceof Article) {
                $this->articleSchema = $thing;
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

        return $name;
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

        $result = "";
        $instructions = $this->recipeSchema->recipeInstructions?->getValues() ?? [];
        $i = 1;
        foreach ($instructions as $step) {
            if (!$step->text) {
                continue;
            }

            $result .= $i . ". " . $step->text->toString() . "\n\n";
            $i++;
        }

        return $result;
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
        $ingredients = $this->recipeSchema->recipeIngredient?->getValues() ?? [];

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

        $image = $mainImage ?? null;
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
