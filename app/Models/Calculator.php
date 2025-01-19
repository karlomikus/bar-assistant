<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use NXP\MathExecutor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorResult;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorSolveRequest;

class Calculator extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CalculatorFactory> */
    use HasFactory;
    use HasBarAwareScope;

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return HasMany<CalculatorBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(CalculatorBlock::class)->orderBy('sort');
    }

    /**
     * @return Collection<int, CalculatorBlock>
     */
    public function getInputs(): Collection
    {
        return $this->blocks->filter(fn (CalculatorBlock $block) => $block->type === CalculatorBlockTypeEnum::Input);
    }

    /**
     * @return Collection<int, CalculatorBlock>
     */
    public function getEvaluations(): Collection
    {
        return $this->blocks->filter(fn (CalculatorBlock $block) => $block->type === CalculatorBlockTypeEnum::Eval);
    }

    public function solve(CalculatorSolveRequest $input): CalculatorResult
    {
        $calculatorResult = new CalculatorResult();
        $executor = new MathExecutor();

        foreach ($this->getInputs() as $block) {
            $value = $input->inputs[$block->variable_name] ?? $block->value;
            $calculatorResult->inputs[$block->variable_name] = $value;

            $executor->setVar($block->variable_name, $value);
        }

        foreach ($this->getEvaluations() as $block) {
            $result = $executor->execute($block->value);
            $formattedResult = number_format(floatval($result), $block->settings?->decimal_places ?? 0);
            $formattedResult = trim(sprintf('%s %s %s', $block->settings?->prefix ?? '', $formattedResult, $block->settings?->suffix ?? ''));

            $calculatorResult->results[$block->variable_name] = $formattedResult;

            $executor->setVar($block->variable_name, $result);
        }

        return $calculatorResult;
    }
}
