<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Kami\Cocktail\Models\Cocktail as CocktailModel;
use Kami\Cocktail\Models\Collection as CollectionModel;

readonly class Collection implements JsonSerializable
{
    /**
     * @param array<Cocktail> $cocktails
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $cocktails = [],
    ) {
    }

    public static function fromModel(CollectionModel $model): self
    {
        return new self(
            $model->name,
            $model->description,
            $model->cocktails->map(function (CocktailModel $cocktail) {
                return Cocktail::fromModel($cocktail);
            })->toArray(),
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        $cocktails = [];
        foreach ($sourceArray['cocktails'] ?? [] as $cocktail) {
            $cocktails[] = Cocktail::fromArray($cocktail);
        }

        return new self(
            $sourceArray['name'],
            $sourceArray['description'] ?? null,
            $cocktails,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'cocktails' => array_map(fn ($model) => $model->toArray(), $this->cocktails),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
