<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Kami\Cocktail\Models\Ingredient as IngredientModel;

readonly class Ingredient implements JsonSerializable
{
    private function __construct(
        public string $id,
        public string $name,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $category = null,
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
            $model->category?->name
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['_id'],
            $sourceArray['name'],
            $sourceArray['strength'] ?? 0.0,
            $sourceArray['description'] ?? null,
            $sourceArray['origin'] ?? null,
            $sourceArray['category'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            '_id' => $this->id,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => $this->description,
            'origin' => $this->origin,
            'category' => $this->category,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
