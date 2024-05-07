<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Image as ImageModel;
use Kami\Cocktail\Models\Cocktail as CocktailModel;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;

readonly class Cocktail implements JsonSerializable
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

    public static function fromModel(CocktailModel $model): self
    {
        $images = $model->images->map(function (ImageModel $image) {
            return Image::fromModel($image);
        })->toArray();

        $ingredients = $model->ingredients->map(function (CocktailIngredientModel $cocktailIngredient) {
            return CocktailIngredient::fromModel($cocktailIngredient);
        })->toArray();

        return new self(
            $model->getExternalId(),
            $model->name,
            $model->instructions,
            $model->created_at->toDateTimeString(),
            $model->updated_at?->toDateTimeString(),
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

    public static function fromArray(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromArray($sourceImage);
        }

        $ingredients = [];
        foreach ($sourceArray['ingredients'] ?? [] as $sourceIngredient) {
            $ingredients[] = CocktailIngredient::fromArray($sourceIngredient);
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

    public function toArray(): array
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
            'images' => array_map(fn ($model) => $model->toArray(), $this->images),
            'ingredients' => array_map(fn ($model) => $model->toArray(), $this->ingredients),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
