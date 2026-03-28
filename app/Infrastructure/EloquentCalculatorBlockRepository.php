<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Calculator\CalculatorBlock;
use BarAssistant\Domain\Calculator\CalculatorBlockId;
use BarAssistant\Domain\Calculator\CalculatorBlockType;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;
use BarAssistant\Domain\Calculator\CalculatorBlockSettings;
use BarAssistant\Domain\Calculator\CalculatorBlockRepository;
use Kami\Cocktail\Models\CalculatorBlock as ModelCalculatorBlock;

final class EloquentCalculatorBlockRepository implements CalculatorBlockRepository
{
    public function findById(CalculatorBlockId $id): ?CalculatorBlock
    {
        $model = ModelCalculatorBlock::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    /**
     * @return CalculatorBlock[]
     */
    public function findByCalculatorId(CalculatorId $calculatorId): array
    {
        $models = ModelCalculatorBlock::where('calculator_id', $calculatorId->value)
            ->orderBy('sort')
            ->get();

        $blocks = [];
        /** @var ModelCalculatorBlock $model */
        foreach ($models as $model) {
            $blocks[] = self::map($model);
        }

        return $blocks;
    }

    public function save(CalculatorBlock $block): CalculatorBlock
    {
        $blockModel = ModelCalculatorBlock::findOrNew($block->getId()?->value);
        $blockModel->calculator_id = $block->getCalculatorId()->value;
        $blockModel->label = $block->getLabel();
        $blockModel->variable_name = $block->getVariableName();
        $blockModel->value = $block->getValue();
        $blockModel->type = CalculatorBlockTypeEnum::from($block->getType()->value);
        $blockModel->sort = $block->getSort();
        $blockModel->description = $block->getDescription();
        $blockModel->settings = $block->getSettings()->toArray();
        $blockModel->save();

        if ($block->isTransient()) {
            $block->setId(new CalculatorBlockId($blockModel->id));
        }

        return $block;
    }

    /**
     * @param CalculatorBlock[] $blocks
     * @return CalculatorBlock[]
     */
    public function saveMany(array $blocks): array
    {
        foreach ($blocks as $block) {
            $this->save($block);
        }

        return $blocks;
    }

    public function delete(CalculatorBlockId $id): void
    {
        ModelCalculatorBlock::destroy($id->value);
    }

    public function deleteByCalculatorId(CalculatorId $calculatorId): void
    {
        ModelCalculatorBlock::where('calculator_id', $calculatorId->value)->delete();
    }

    private static function map(ModelCalculatorBlock $model): CalculatorBlock
    {
        $calculatorId = new CalculatorId($model->calculator_id);
        /** @var array<string, mixed> $settingsData */
        $settingsData = (array) ($model->settings ?? []);
        $block = CalculatorBlock::create(
            calculatorId: $calculatorId,
            label: $model->label,
            type: CalculatorBlockType::fromString($model->type->value),
            variableName: $model->variable_name,
            value: $model->value,
            sort: $model->sort,
            description: $model->description,
            settings: CalculatorBlockSettings::fromArray($settingsData),
        );

        $block->setId(new CalculatorBlockId($model->id));

        return $block;
    }
}
