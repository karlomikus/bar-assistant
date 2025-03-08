<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsCSV;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Image as ImageModel;
use Kami\Cocktail\Models\Ingredient as IngredientModel;
use Kami\Cocktail\Models\IngredientPrice as IngredientPriceModel;

readonly class Ingredient implements SupportsDataPack, SupportsCSV
{
    /**
     * @param array<Image> $images
     * @param array<IngredientBasic> $ingredientParts
     * @param array<IngredientPrice> $prices
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
        public array $prices = [],
        public ?string $calculatorId = null,
        public ?float $sugarContent = null,
        public ?float $acidity = null,
        public ?string $distillery = null,
    ) {
    }

    public static function fromModel(IngredientModel $model, bool $useFileURI = false): self
    {
        $images = $model->images->map(function (ImageModel $image) use ($useFileURI) {
            return Image::fromModel($image, $useFileURI);
        })->toArray();

        $ingredientParts = $model->ingredientParts->map(function (ComplexIngredient $part) {
            return IngredientBasic::fromModel($part->ingredient);
        })->toArray();

        $ingredientPrices = $model->prices->map(function (IngredientPriceModel $price) {
            return IngredientPrice::fromModel($price);
        })->toArray();

        return new self(
            $model->getExternalId(),
            $model->name,
            $model->parent_ingredient_id ? $model->parentIngredient->getExternalId() : null,
            $model->strength,
            $model->description,
            $model->origin,
            $model->getMaterializedPathAsString(),
            $model->color,
            $model->created_at->toAtomString(),
            $model->updated_at?->toAtomString(),
            $images,
            $ingredientParts,
            $ingredientPrices,
            $model->calculator?->getExternalId(),
            $model->sugar_g_per_ml,
            $model->acidity,
            $model->distillery,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromDataPackArray($sourceImage);
        }

        $ingredientParts = [];
        foreach ($sourceArray['ingredient_parts'] ?? [] as $ingredient) {
            $ingredientParts[] = IngredientBasic::fromDataPackArray($ingredient);
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
            [],
            $sourceArray['calculator_id'] ?? null,
            $sourceArray['sugar_g_per_ml'] ?? null,
            $sourceArray['acidity'] ?? null,
            $sourceArray['distillery'] ?? null,
        );
    }

    public function toDataPackArray(): array
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
            'images' => array_map(fn ($model) => $model->toDataPackArray(), $this->images),
            'ingredient_parts' => array_map(fn ($model) => $model->toDataPackArray(), $this->ingredientParts),
            'prices' => array_map(fn ($model) => $model->toDataPackArray(), $this->prices),
            'calculator_id' => $this->calculatorId,
            'sugar_g_per_ml' => $this->sugarContent,
            'acidity' => $this->acidity,
            'distillery' => $this->distillery,
        ];
    }

    public static function fromCSV(array $sourceArray): self
    {
        $sourceArray = array_change_key_case($sourceArray, CASE_LOWER);

        return new self(
            id: 'CSV',
            name: $sourceArray['name'],
            parentId: null,
            strength: isset($sourceArray['strength']) ? floatval($sourceArray['strength']) : 0.0,
            description: blank($sourceArray['description']) ? null : $sourceArray['description'],
            origin: blank($sourceArray['origin']) ? null : $sourceArray['origin'],
            color: blank($sourceArray['color']) ? null : $sourceArray['color'],
            createdAt: null,
            updatedAt: null,
            images: [],
            ingredientParts: [],
            prices: [],
            calculatorId: null,
            sugarContent: blank($sourceArray['sugar_g_per_ml']) ? null : $sourceArray['sugar_g_per_ml'],
            acidity: blank($sourceArray['acidity']) ? null : $sourceArray['acidity'],
            distillery: blank($sourceArray['distillery']) ? null : $sourceArray['distillery'],
        );
    }
}
