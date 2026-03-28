<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Throwable;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Calculator\Calculator;
use BarAssistant\Domain\Calculator\CalculatorId;
use Kami\Cocktail\Models\Calculator as ModelCalculator;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;
use BarAssistant\Domain\Calculator\CalculatorRepository;
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
            $calculatorModel->save();

            if ($calculator->isTransient()) {
                $calculator = $calculator->setId(new CalculatorId($calculatorModel->id));
            }

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

                if ($block->isTransient()) {
                    $block->setId(new \BarAssistant\Domain\Calculator\CalculatorBlockId($blockModel->id));
                }
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();

        return $calculator;
    }

    public function saveWithBlocks(Calculator $calculator): Calculator
    {
        DB::beginTransaction();
        try {
            $calculatorModel = ModelCalculator::findOrFail($calculator->getId()?->value);
            $calculatorModel->name = $calculator->getName();
            $calculatorModel->description = $calculator->getDescription();
            $calculatorModel->save();

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
        );

        $calculator->setId(new CalculatorId($model->id));

        return $calculator;
    }
}
