<?php

declare(strict_types=1);

namespace Kami\Cocktail\Scraper;

use Symfony\Component\Uid\Ulid;
use Kami\RecipeUtils\Parser\Parser;
use Kami\RecipeUtils\ParserFactory;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\Cocktail\External\Model\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Kami\Cocktail\External\Model\Cocktail;
use Kami\Cocktail\External\Model\Ingredient;
use Kami\Cocktail\Exceptions\ScraperMissingException;

abstract class AbstractSite implements Site
{
    protected readonly Crawler $crawler;
    protected readonly Parser $ingredientParser;

    public function __construct(
        protected readonly string $url,
        protected readonly string $content = '',
    ) {
        $this->crawler = new Crawler($content);
        $this->ingredientParser = ParserFactory::make();
    }

    /**
     * Array with a list of support sites. All sites must be defined
     * with protocol (ex: https://) and end without slash
     *
     * @return array<string>
     */
    abstract public static function getSupportedUrls(): array;

    /**
     * Cocktail name
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * Cocktail description, can support markdown
     *
     * @return null|string
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Cocktail source URL
     *
     * @return null|string
     */
    public function source(): ?string
    {
        return null;
    }

    /**
     * Cocktail preparation instructions, can support markdown
     *
     * @return null|string
     */
    abstract public function instructions(): ?string;

    /**
     * Cocktail tags
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [];
    }

    /**
     * Cocktail serving glass
     *
     * @return null|string
     */
    public function glass(): ?string
    {
        return null;
    }

    /**
     * Array containing cocktail ingredients
     *
     * @return array<RecipeIngredient>
     */
    public function ingredients(): array
    {
        return [];
    }

    /**
     * Cocktail garnish, can support markdown
     *
     * @return null|string
     */
    public function garnish(): ?string
    {
        return null;
    }

    /**
     * Array containing image information
     *
     * @return null|array{"uri": string|null, "copyright": string|null}
     */
    public function image(): ?array
    {
        return null;
    }

    /**
     * Cocktail method (shake, stir...)
     *
     * @return null|string
     */
    public function method(): ?string
    {
        return null;
    }

    /**
     * Cocktail information as array
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $ingredients = $this->ingredients();
        $ingredients = array_map(function (RecipeIngredient $recipeIngredient, int $sort) {
            return [
                '_id' => Ulid::generate(),
                'name' => $this->clean(ucfirst($recipeIngredient->name)),
                'amount' => $recipeIngredient->amount->getValue(),
                'amount_max' => $recipeIngredient->amountMax?->getValue(),
                'units' => $recipeIngredient->units === '' ? null : $recipeIngredient->units,
                'note' => $recipeIngredient->comment === '' ? null : $recipeIngredient->comment,
                'source' => $this->clean($recipeIngredient->source),
                'optional' => false,
                'sort' => $sort + 1,
            ];
        }, $ingredients, array_keys($ingredients));

        $meta = array_map(function (array $org) {
            return [
                '_id' => $org['_id'],
                'source' => $org['source'],
            ];
        }, $ingredients);

        $image = $this->convertImagesToDataUri();
        $images = [];
        if ($image['uri']) {
            $images[] = $image;
        }

        $name = $this->clean($this->name());
        if (!$name) {
            throw new ScraperMissingException('Unsupported site or no recipes found');
        }

        $cocktail = Cocktail::fromDraft2Array([
            'name' => $name,
            'instructions' => $this->instructions(),
            'description' => $this->cleanDescription($this->description()),
            'source' => $this->source(),
            'glass' => $this->glass(),
            'garnish' => $this->clean($this->garnish()),
            'tags' => $this->tags(),
            'method' => $this->method(),
            'images' => $images,
            'ingredients' => $ingredients,
        ]);

        $model = new Schema(
            $cocktail,
            array_map(fn ($ingredient) => Ingredient::fromDraft2Array($ingredient), $ingredients),
        );

        return [
            'schema_version' => $model::SCHEMA_VERSION,
            'schema' => $model->toDraft2Array(),
            'scraper_meta' => $meta,
        ];
    }

    /**
     * Cleans up white space in a string and decodes HTML entities.
     *
     * @param ?string $str The string to clean up.
     * @return ?string The cleaned up string.
     */
    protected function clean(?string $str): ?string
    {
        if (!$str) {
            return null;
        }

        $str = str_replace(' ', " ", $str);
        $str = preg_replace("/\s+/u", " ", $str);

        return html_entity_decode($str, encoding: 'UTF-8');
    }

    /**
     * Clean up the cocktail description.
     *
     * This function will be used to clean up the string produced by {@see AbstractSiteExtractor::description() description()}.
     * Can be overriden by scrapers that do the clean up internally within {@see AbstractSiteExtractor::description() description()}
     * so that they can, for example, produce Markdown with properly separated paragraphs.
     *
     * @param ?string $description The cocktail description to clean up.
     * @return ?string The cleaned up description.
     */
    protected function cleanDescription(?string $description): ?string
    {
        return $this->clean($description);
    }

    /**
     * @return array<string, string|null>
     */
    private function convertImagesToDataUri(): array
    {
        $image = $this->image();
        if ($image['uri'] && !blank($image['uri'])) {
            $url = parse_url($image['uri']);
            $cleanUrl = ($url['scheme'] ?? '') . '://' . ($url['host'] ?? '') . (isset($url['path']) ? $url['path'] : '');

            $dataUri = null;
            $type = pathinfo($cleanUrl, PATHINFO_EXTENSION);
            if ($data = file_get_contents($cleanUrl)) {
                $dataUri = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }

            return [
                'uri' => $dataUri,
                'copyright' => $image['copyright'],
            ];
        }

        return $image;
    }
}
