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
        public ?string $units = null,
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
            id: $model->getExternalId(),
            name: $model->name,
            parentId: $model->parent_ingredient_id ? $model->parentIngredient->getExternalId() : null,
            strength: $model->strength,
            description: $model->description,
            origin: $model->origin,
            color: $model->color,
            category: $model->getMaterializedPathAsString(),
            createdAt: $model->created_at->toAtomString(),
            updatedAt: $model->updated_at?->toAtomString(),
            images: $images,
            ingredientParts: $ingredientParts,
            prices: $ingredientPrices,
            calculatorId: $model->calculator?->getExternalId(),
            sugarContent: $model->sugar_g_per_ml,
            acidity: $model->acidity,
            distillery: $model->distillery,
            units: $model->getDefaultUnits()?->value,
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
            id: $sourceArray['_id'],
            name: $sourceArray['name'],
            parentId: $sourceArray['_parent_id'] ?? null,
            strength: $sourceArray['strength'] ?? 0.0,
            description: $sourceArray['description'] ?? null,
            origin: $sourceArray['origin'] ?? null,
            color: $sourceArray['color'] ?? null,
            category: $sourceArray['category'] ?? null,
            createdAt: $sourceArray['created_at'] ?? null,
            updatedAt: $sourceArray['updated_at'] ?? null,
            images: $images,
            ingredientParts: $ingredientParts,
            prices: [],
            calculatorId: $sourceArray['calculator_id'] ?? null,
            sugarContent: $sourceArray['sugar_g_per_ml'] ?? null,
            acidity: $sourceArray['acidity'] ?? null,
            distillery: $sourceArray['distillery'] ?? null,
            units: $sourceArray['units'] ?? null,
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
            'units' => $this->units,
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
            units: blank($sourceArray['units']) ? null : $sourceArray['units'],
        );
    }
}
