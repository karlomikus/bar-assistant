<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Calculator\Calculator;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Calculator\CalculatorBlock;
use BarAssistant\Domain\Calculator\CalculatorBlockType;
use BarAssistant\Domain\Calculator\CalculatorBlockSettings;
use Kami\Cocktail\Infrastructure\EloquentCalculatorRepository;

final class EloquentCalculatorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_calculator(): void
    {
        $repository = new EloquentCalculatorRepository();
        $membership = $this->setupBarMembership();

        $result = Calculator::create(
            barId: new BarId($membership->bar_id),
            name: 'Dolor sit amet',
            description: 'Lorem ipsum',
            recordTimestamps: RecordTimestamps::createdNow(),
            blocks: [
                CalculatorBlock::create(
                    label: 'Block 1',
                    type: CalculatorBlockType::Input,
                    variableName: 'var1',
                    value: '100',
                    sort: 1,
                    description: 'Demo',
                    settings: CalculatorBlockSettings::default(),
                ),
                CalculatorBlock::create(
                    label: 'Eval 1',
                    type: CalculatorBlockType::Eval,
                    variableName: 'eval1',
                    value: 'var1 + 100',
                    sort: 2,
                    description: 'Eval description',
                    settings: CalculatorBlockSettings::default(),
                ),
            ],
        );

        $result = $repository->save($result);

        $this->assertDatabaseHas('calculators', [
            'id' => $result->getId()->value,
            'bar_id' => $membership->bar_id,
            'name' => 'Dolor sit amet',
            'description' => 'Lorem ipsum',
            'updated_at' => null,
        ]);
        $this->assertDatabaseCount('calculator_blocks', 2);
        $this->assertDatabaseHas('calculator_blocks', [
            'calculator_id' => $result->getId()->value,
            'label' => 'Block 1',
            'type' => 'input',
            'variable_name' => 'var1',
            'value' => '100',
            'sort' => 1,
            'description' => 'Demo',
        ]);
        $this->assertDatabaseHas('calculator_blocks', [
            'calculator_id' => $result->getId()->value,
            'label' => 'Eval 1',
            'type' => 'eval',
            'variable_name' => 'eval1',
            'value' => 'var1 + 100',
            'sort' => 2,
            'description' => 'Eval description',
        ]);
    }
}
