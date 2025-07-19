<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper\Sites;

use Throwable;
use Kami\RecipeUtils\AmountValue;
use Kami\Cocktail\Scraper\SchemaModel;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\Cocktail\Scraper\Concerns\ReadsLDJson;
use Kami\Cocktail\Scraper\AbstractSite;
use Kami\Cocktail\Scraper\Concerns\ReadsHTMLSchema;

class DefaultScraper extends AbstractSite
{
    use ReadsLDJson;
    use ReadsHTMLSchema;

    protected ?SchemaModel $schemaModel = null;

    public function __construct(string $url, string $content = '')
    {
        parent::__construct($url, $content);

        $jsonLdNodes = $this->crawler->filterXPath('//script[@type="application/ld+json"]');
        $htmlSchemaNodes = $this->crawler->filterXPath('//*[@itemtype="http://schema.org/Recipe"]//*[@itemprop]');

        $schema = null;
        if ($jsonLdNodes->count() > 0) {
            $schema = $this->readJSON($jsonLdNodes);
        }

        if (!$schema && $htmlSchemaNodes->count() > 0) {
            $schema = $this->readHTML($htmlSchemaNodes);
        }

        $this->schemaModel = $schema;
    }

    public static function getSupportedUrls(): array
    {
        return [];
    }

    public function name(): string
    {
        $name = $this->schemaModel->name ?? null;

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
        $description = $this->schemaModel->description ?? null;

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
        $instructions = $this->schemaModel->instructions ?? [];

        $instructions = array_map(function ($instructionStep) {
            if (is_string($instructionStep)) {
                return $instructionStep;
            } elseif (is_array($instructionStep) && array_key_exists('text', $instructionStep)) {
                return $instructionStep['text'];
            } else {
                return null;
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
            if (is_null($step)) {
                continue;
            }

            $result .= $i . ". " . trim($step) . "\n";
            $i++;
        }

        return trim($result);
    }

    public function tags(): array
    {
        return $this->schemaModel->tags ?? [];
    }

    public function glass(): ?string
    {
        return null;
    }

    public function ingredients(): array
    {
        $result = [];

        $ingredients = $this->schemaModel->ingredients ?? [];

        foreach ($ingredients as $ingredient) {
            $ingredient = html_entity_decode($ingredient, ENT_SUBSTITUTE | ENT_HTML5); // Convert entities to correct chars
            $ingredient = e(trim($ingredient, " \n\r\t\v\x00\"\'"));
            $recipeIngredient = $this->ingredientParser->parseLine($ingredient);

            if (empty($recipeIngredient->amount) || empty($recipeIngredient->name) || empty($recipeIngredient->units)) {
                continue;
            }

            $result[] = new RecipeIngredient(
                ucfirst(e($recipeIngredient->name)),
                new AmountValue((float) number_format($recipeIngredient->amount->getValue(), 2, '.', '')),
                $recipeIngredient->units,
                $recipeIngredient->source,
                $recipeIngredient->comment,
                $recipeIngredient->amountMax
            );
        }

        return $result;
    }

    public function image(): ?array
    {
        return [
            'uri' => $this->schemaModel?->image,
            'copyright' => $this->schemaModel?->author,
        ];
    }
}
