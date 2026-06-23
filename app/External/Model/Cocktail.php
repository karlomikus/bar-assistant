<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\SupportsJSONLD;
use Kami\Cocktail\External\SupportsSchema4;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Image as ImageModel;
use Kami\Cocktail\Models\Cocktail as CocktailModel;
use Kami\Cocktail\Models\ValueObjects\CocktailIngredientFormatter;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;

readonly class Cocktail implements SupportsDataPack, SupportsSchema4, SupportsJSONLD
{
    /**
     * @param array<string> $tags
     * @param array<string> $utensils
     * @param array<Image> $images
     * @param array<CocktailIngredient> $ingredients
     */
    private function __construct(
        public string $id,
        public string $name,
        public string $instructions,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $description = null,
        public ?string $source = null,
        public ?string $garnish = null,
        public ?float $abv = null,
        public array $tags = [],
        public ?string $glass = null,
        public ?string $method = null,
        public array $utensils = [],
        public array $images = [],
        public array $ingredients = [],
        public ?string $parentCocktailId = null,
        public ?int $year = null,
        public ?string $author = null,
    ) {
    }

    public static function fromModel(CocktailModel $model, bool $useFileURI = false, ?Units $toUnits = null): self
    {
        $images = $model->images->map(fn (ImageModel $image) => Image::fromModel($image, $useFileURI))->toArray();

        $ingredients = $model->ingredients->map(fn (CocktailIngredientModel $cocktailIngredient) => CocktailIngredient::fromModel($cocktailIngredient, $toUnits))->toArray();

        return new self(
            $model->getExternalId(),
            $model->name,
            $model->instructions,
            $model->created_at->toAtomString(),
            $model->updated_at?->toAtomString(),
            $model->description,
            $model->source,
            $model->garnish,
            $model->abv,
            $model->tags->pluck('name')->toArray(),
            $model->glass?->name,
            $model->method?->name,
            $model->utensils->pluck('name')->toArray(),
            $images,
            $ingredients,
            $model->parentCocktail?->getExternalId(),
            $model->year,
            $model->author,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromDataPackArray($sourceImage);
        }

        $ingredients = [];
        foreach ($sourceArray['ingredients'] ?? [] as $sourceIngredient) {
            $ingredients[] = CocktailIngredient::fromDataPackArray($sourceIngredient);
        }

        return new self(
            $sourceArray['_id'] ?? Str::slug($sourceArray['name']),
            $sourceArray['name'],
            $sourceArray['instructions'],
            $sourceArray['created_at'] ?? null,
            $sourceArray['updated_at'] ?? null,
            $sourceArray['description'] ?? null,
            $sourceArray['source'] ?? null,
            $sourceArray['garnish'] ?? null,
            $sourceArray['abv'] ?? null,
            $sourceArray['tags'] ?? [],
            $sourceArray['glass'] ?? null,
            $sourceArray['method'] ?? null,
            $sourceArray['utensils'] ?? [],
            $images,
            $ingredients,
            $sourceArray['parent_cocktail_id'] ?? null,
            $sourceArray['year'] ?? null,
            isset($sourceArray['author']) && is_string($sourceArray['author']) ? $sourceArray['author'] : null,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->id,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'description' => $this->description,
            'source' => $this->source,
            'garnish' => $this->garnish,
            'abv' => $this->abv,
            'tags' => $this->tags,
            'glass' => $this->glass,
            'method' => $this->method,
            'utensils' => $this->utensils,
            'parent_cocktail_id' => $this->parentCocktailId,
            'year' => $this->year,
            'author' => $this->author,
            'images' => array_map(fn ($model) => $model->toDataPackArray(), $this->images),
            'ingredients' => array_map(fn ($model) => $model->toDataPackArray(), $this->ingredients),
        ];
    }

    public static function fromSchema4Array(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromSchema4Array($sourceImage);
        }

        $ingredients = [];
        foreach ($sourceArray['ingredients'] ?? [] as $sourceIngredient) {
            $ingredients[] = CocktailIngredient::fromSchema4Array($sourceIngredient);
        }

        return new self(
            $sourceArray['_id'] ?? Str::slug($sourceArray['name']),
            $sourceArray['name'],
            $sourceArray['instructions'],
            $sourceArray['created_at'] ?? null,
            null,
            $sourceArray['description'] ?? null,
            $sourceArray['source'] ?? null,
            $sourceArray['garnish'] ?? null,
            $sourceArray['abv'] ?? null,
            $sourceArray['tags'] ?? [],
            $sourceArray['glass'] ?? null,
            $sourceArray['method'] ?? null,
            [],
            $images,
            $ingredients,
            null,
            null,
            isset($sourceArray['author']) && is_string($sourceArray['author']) ? $sourceArray['author'] : null,
        );
    }

    public function toSchema4Array(): array
    {
        return [
            'name' => $this->name,
            'instructions' => $this->instructions,
            'created_at' => $this->createdAt,
            'description' => $this->description,
            'source' => $this->source,
            'garnish' => $this->garnish,
            'abv' => $this->abv,
            'tags' => $this->tags,
            'glass' => $this->glass,
            'method' => $this->method,
            'images' => array_map(fn ($model) => $model->toSchema4Array(), $this->images),
            'ingredients' => array_map(fn ($model) => $model->toSchema4Array(), $this->ingredients),
            'author' => $this->author,
        ];
    }

    public function toJSONLD(): string
    {
        $mainImage = $this->images[0] ?? null;

        $image = [];
        if ($mainImage) {
            $image = [
                "@type" => "ImageObject",
                "author" => e($mainImage->copyright),
                "url" => $mainImage->uri,
            ];
        }

        $data = array_merge([
            "@context" => "https://schema.org",
            "@type" => "Recipe",
            "author" => [
                '@type' => 'Organization',
                'name' => "Bar Assistant | Source: " . e($this->source)
            ],
            "name" => e($this->name),
            "datePublished" => $this->createdAt,
            "description" => e($this->description),
            'recipeInstructions' => e($this->instructions),
            "cookingMethod" => $this->method,
            "recipeYield" => "1 drink",
            "recipeCategory" => "Drink",
            "recipeCuisine" => "Cocktail",
            "keywords" => implode(', ', $this->tags),
            "recipeIngredient" => array_map(fn (CocktailIngredient $ci) => (new CocktailIngredientFormatter($ci->amount, $ci->ingredient->name, $ci->optional))->format(), $this->ingredients),
        ], ['image' => $image]);

        if ($data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
            return $data;
        }

        return '{}';
    }
}
