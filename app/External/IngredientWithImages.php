<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\Image as ImageModel;
use Kami\Cocktail\Models\Ingredient as IngredientModel;

readonly class IngredientWithImages implements JsonSerializable
{
    /**
     * @param array<Image> $images
     */
    private function __construct(
        public string $id,
        public string $name,
        public ?string $parentId = null,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $color = null,
        public ?string $category = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public array $images = [],
        public array $ingredientParts = [],
    ) {
    }

    public static function fromModel(IngredientModel $model): self
    {
        $images = $model->images->map(function (ImageModel $image) {
            return Image::fromModel($image);
        })->toArray();

        $ingredientParts = $model->ingredientParts->map(function (ComplexIngredient $part) {
            return Ingredient::fromModel($part->ingredient);
        })->toArray();

        return new self(
            $model->getExternalId(),
            $model->name,
            $model->parent_ingredient_id ? $model->parentIngredient->getExternalId() : null,
            $model->strength,
            $model->description,
            $model->origin,
            $model->color,
            $model->category?->name ?? null,
            $model->created_at->toAtomString(),
            $model->updated_at?->toAtomString(),
            $images,
            $ingredientParts,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromArray($sourceImage);
        }

        $ingredientParts = [];
        foreach ($sourceArray['ingredient_parts'] ?? [] as $ingredient) {
            $ingredientParts[] = Ingredient::fromArray($ingredient);
        }

        return new self(
            $sourceArray['_id'],
            $sourceArray['name'],
            $sourceArray['_parent_id'] ?? null,
            $sourceArray['strength'] ?? 0.0,
            $sourceArray['description'] ?? null,
            $sourceArray['origin'] ?? null,
            $sourceArray['color'] ?? null,
            $sourceArray['category'] ?? null,
            $sourceArray['created_at'] ?? null,
            $sourceArray['updated_at'] ?? null,
            $images,
            $ingredientParts,
        );
    }

    public function toArray(): array
    {
        return [
            '_id' => $this->id,
            '_parent_id' => $this->parentId,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => $this->description,
            'origin' => $this->origin,
            'color' => $this->color,
            'category' => $this->category,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'images' => array_map(fn ($model) => $model->toArray(), $this->images),
            'ingredient_parts' => array_map(fn ($model) => $model->toArray(), $this->ingredientParts),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
