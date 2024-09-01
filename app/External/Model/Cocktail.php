<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsJSONLD;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Image as ImageModel;
use Kami\Cocktail\Models\Cocktail as CocktailModel;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;

readonly class Cocktail implements SupportsDataPack, SupportsDraft2, SupportsJSONLD
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
    ) {
    }

    public static function fromModel(CocktailModel $model, bool $useFileURI = false): self
    {
        $images = $model->images->map(function (ImageModel $image) use ($useFileURI) {
            return Image::fromModel($image, $useFileURI);
        })->toArray();

        $ingredients = $model->ingredients->map(function (CocktailIngredientModel $cocktailIngredient) {
            return CocktailIngredient::fromModel($cocktailIngredient);
        })->toArray();

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
            'images' => array_map(fn ($model) => $model->toDataPackArray(), $this->images),
            'ingredients' => array_map(fn ($model) => $model->toDataPackArray(), $this->ingredients),
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromDraft2Array($sourceImage);
        }

        $ingredients = [];
        foreach ($sourceArray['ingredients'] ?? [] as $sourceIngredient) {
            $ingredients[] = CocktailIngredient::fromDraft2Array($sourceIngredient);
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
        );
    }

    public function toDraft2Array(): array
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
            'images' => array_map(fn ($model) => $model->toDraft2Array(), $this->images),
            'ingredients' => array_map(fn ($model) => $model->toDraft2Array(), $this->ingredients),
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
                'name' => "Recipe exported from Bar Assistant"
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
            "recipeIngredient" => array_map(function (CocktailIngredient $ci) {
                return $ci->amount . ' ' . $ci->units . ' ' . $ci->ingredient->name;
            }, $this->ingredients),
        ], $image);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ;
    }
}
