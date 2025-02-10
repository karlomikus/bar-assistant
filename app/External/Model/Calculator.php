<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\CalculatorBlock;
use Kami\Cocktail\Models\Calculator as CalculatorModel;

readonly class Calculator implements SupportsDataPack
{
    /**
     * @param array<string, mixed> $blocks
     */
    private function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public array $blocks = [],
    ) {
    }

    public static function fromModel(CalculatorModel $model): self
    {
        return new self(
            $model->getExternalId(),
            $model->name,
            $model->description,
            $model->blocks->map(function (CalculatorBlock $block) {
                return [
                    'label' => $block->label,
                    'variable_name' => $block->variable_name,
                    'value' => $block->value,
                    'type' => $block->type,
                    'settings' => $block->settings,
                    'description' => $block->description,
                    'sort' => $block->sort,
                ];
            })->toArray()
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['_id'],
            $sourceArray['name'],
            $sourceArray['description'] ?? null,
            $sourceArray['blocks'] ?? [],
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'blocks' => $this->blocks,
        ];
    }
}
