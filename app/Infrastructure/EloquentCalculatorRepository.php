<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Throwable;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Calculator\Calculator;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Calculator\CalculatorBlock;
use BarAssistant\Domain\Calculator\CalculatorBlockType;
use Kami\Cocktail\Models\Calculator as ModelCalculator;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;
use BarAssistant\Domain\Calculator\CalculatorRepository;
use BarAssistant\Domain\Calculator\CalculatorBlockSettings;
use Kami\Cocktail\Models\CalculatorBlock as ModelCalculatorBlock;

final class EloquentCalculatorRepository implements CalculatorRepository
{
    public function findById(CalculatorId $id): ?Calculator
    {
        $model = ModelCalculator::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Calculator $calculator): Calculator
    {
        DB::beginTransaction();
        try {
            $calculatorModel = ModelCalculator::findOrNew($calculator->getId()?->value);
            $calculatorModel->bar_id = $calculator->getBarId()->value;
            $calculatorModel->name = $calculator->getName();
            $calculatorModel->description = $calculator->getDescription();
            $calculatorModel->created_at = $calculator->getRecordTimestamps()->getCreatedAt();
            $calculatorModel->updated_at = null;
            if ($calculator->getRecordTimestamps()->wasUpdated()) {
                $calculatorModel->updated_at = $calculator->getRecordTimestamps()->getUpdatedAt();
            }
            $calculatorModel->save();

            if ($calculator->isTransient()) {
                $calculator = $calculator->setId(new CalculatorId($calculatorModel->id));
            }

            $calculatorModel->blocks()->delete();
            foreach ($calculator->getBlocks() as $block) {
                $blockModel = new ModelCalculatorBlock();
                $blockModel->calculator_id = $calculatorModel->id;
                $blockModel->label = $block->getLabel();
                $blockModel->variable_name = $block->getVariableName();
                $blockModel->value = $block->getValue();
                $blockModel->type = CalculatorBlockTypeEnum::from($block->getType()->value);
                $blockModel->sort = $block->getSort();
                $blockModel->description = $block->getDescription();
                $blockModel->settings = $block->getSettings()->toArray();
                $blockModel->save();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();

        return $calculator;
    }

    public function delete(CalculatorId $id): void
    {
        ModelCalculator::destroy($id->value);
    }

    private static function map(ModelCalculator $model): Calculator
    {
        $calculator = Calculator::create(
            barId: new BarId($model->bar_id),
            name: $model->name,
            description: $model->description,
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
        );

        $calculator->setId(new CalculatorId($model->id));

        $blocks = [];
        /** @var ModelCalculatorBlock $blockModel */
        foreach ($model->blocks as $blockModel) {
            /** @var array<string, mixed> $settingsData */
            $settingsData = (array) ($blockModel->settings ?? []);
            $block = CalculatorBlock::create(
                label: $blockModel->label,
                type: CalculatorBlockType::from($blockModel->type->value),
                variableName: $blockModel->variable_name,
                value: $blockModel->value,
                sort: $blockModel->sort,
                description: $blockModel->description,
                settings: CalculatorBlockSettings::fromArray($settingsData),
            );

            $blocks[] = $block;
        }

        return $calculator->replaceBlocks($blocks);
    }
}
