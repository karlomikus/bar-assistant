<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Ingredient as IngredientModel;

readonly class IngredientBasic implements SupportsDraft2, SupportsDataPack
{
    private function __construct(
        public string $id,
        public string $name,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
    ) {
    }

    public static function fromModel(IngredientModel $model): self
    {
        return new self(
            $model->getExternalId(),
            $model->name,
            $model->strength,
            $model->description,
            $model->origin,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['_id'],
            $sourceArray['name'],
            $sourceArray['strength'] ?? 0.0,
            $sourceArray['description'] ?? null,
            $sourceArray['origin'] ?? null,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->id,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => $this->description,
            'origin' => $this->origin,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        return self::fromDataPackArray($sourceArray);
    }

    public function toDraft2Array(): array
    {
        return $this->toDataPackArray();
    }
}
