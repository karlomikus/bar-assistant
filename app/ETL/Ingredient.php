<?php

declare(strict_types=1);

namespace Kami\Cocktail\ETL;

use JsonSerializable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Ingredient as IngredientModel;

readonly class Ingredient implements JsonSerializable
{
    public function __construct(
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
            Str::slug($model->name),
            $model->name,
            $model->strength,
            $model->description,
            $model->origin
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
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
