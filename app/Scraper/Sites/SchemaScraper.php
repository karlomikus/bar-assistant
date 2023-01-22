<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Illuminate\Support\Arr;
use Kami\Cocktail\Scraper\IngredientParser;
use Kami\Cocktail\Exceptions\ScrapeException;
use Kami\Cocktail\Scraper\AbstractSiteExtractor;

class SchemaScraper extends AbstractSiteExtractor
{
    private array $schema;

    public function __construct(string $url)
    {
        parent::__construct($url);

        $schemaPath = $this->crawler->filterXPath('//script[@type="application/ld+json"]');

        if ($schemaPath->count() === 0) {
            throw new ScrapeException('JSON+LD schema not found on this site.');
        }

        $parsedSchema = json_decode($schemaPath->first()->text(), true);
        if (count($parsedSchema) === 1) {
            $this->schema = $parsedSchema[0];
        } else {
            $this->schema = $parsedSchema;
        }
    }

    public static function getSupportedUrls(): array
    {
        return [];
    }

    public function name(): string
    {
        return Arr::get($this->schema, 'name');
    }

    public function description(): ?string
    {
        return Arr::get($this->schema, 'description');
    }

    public function source(): ?string
    {
        return null;
    }

    public function instructions(): ?string
    {
        $result = '';
        $instructions = Arr::get($this->schema, 'recipeInstructions');
        $i = 1;
        foreach ($instructions as $step) {
            $result .= $i . ". " . $step['text'] . "\n\n";
            $i++;
        }

        return $result;
    }

    public function tags(): array
    {
        return [];
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        $result = [];

        foreach (Arr::get($this->schema, 'recipeIngredient') as $ingredient) {
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

    public function garnish(): ?string
    {
        return null;
    }

    public function image(): ?array
    {
        $image = Arr::get($this->schema, 'image.url');
        $copyright = Arr::get($this->schema, 'publisher.name');

        return [
            'url' => $image,
            'copyright' => $copyright,
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'source' => $this->source(),
            'glass' => $this->glass(),
            'instructions' => $this->instructions(),
            'garnish' => $this->garnish(),
            'tags' => $this->tags(),
            'image' => $this->image(),
            'ingredients' => $this->ingredients(),
        ];
    }
}
