<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Calculator;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\CalculatorBlock;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalculatorControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_all_calculators_response(): void
    {
        Calculator::factory()->recycle($this->barMembership->bar)->count(10)->create();
        Calculator::factory()->count(10)->create();

        $response = $this->getJson('/api/calculators', ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_calculator_response(): void
    {
        $calc = Calculator::factory()
            ->recycle($this->barMembership->bar)
            ->create([
                'name' => 'calculator 1',
                'description' => 'calculator 1 Description',
            ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 1',
            'variable_name' => 'variable_name 1',
            'value' => '1 + 2',
            'sort' => 1,
            'type' => 'eval',
            'description' => 'description 1',
            'settings' => ['suffix' => 'g'],
        ]);

        $response = $this->getJson('/api/calculators/' . $calc->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'calculator 1')
                ->where('data.description', 'calculator 1 Description')
                ->has('data.blocks', 1)
                ->where('data.blocks.0.label', 'label 1')
                ->where('data.blocks.0.variable_name', 'variable_name 1')
                ->where('data.blocks.0.value', '1 + 2')
                ->where('data.blocks.0.sort', 1)
                ->where('data.blocks.0.type', 'eval')
                ->where('data.blocks.0.description', 'description 1')
                ->where('data.blocks.0.settings.suffix', 'g')
                ->etc()
        );
    }

    public function test_save_calculator_response(): void
    {
        $response = $this->postJson('/api/calculators', [
            'name' => 'calc 1',
            'description' => 'calc 1 Description',
            'blocks' => [
                [
                    'label' => 'label 1',
                    'variable_name' => 'variable_name 1',
                    'value' => '1 + 2',
                    'sort' => 1,
                    'type' => 'eval',
                    'description' => 'description 1',
                    'settings' => ['suffix' => 'suf', 'prefix' => 'pre'],
                ],
            ],
        ], ['Bar-Assistant-Bar-Id' => $this->barMembership->bar_id]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'calc 1')
                ->where('data.description', 'calc 1 Description')
                ->has('data.blocks', 1)
                ->etc()
        );
    }

    public function test_save_calculator_forbidden_response(): void
    {
        $anotherBar = Bar::factory()->create();

        $response = $this->postJson('/api/calculators', [
            'name' => 'calc 1'
        ], ['Bar-Assistant-Bar-Id' => $anotherBar->id]);

        $response->assertForbidden();
    }

    public function test_update_calculator_response(): void
    {
        $glass = Calculator::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'calculator 1',
            'description' => 'calculator 1 Description',
        ]);

        $response = $this->putJson('/api/calculators/' . $glass->id, [
            'name' => 'Calc updated',
            'description' => 'Calc updated Description',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Calc updated')
                ->where('data.description', 'Calc updated Description')
                ->etc()
        );
    }

    public function test_delete_calculator_response(): void
    {
        $calc = Calculator::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
        ]);
        CalculatorBlock::factory()->for($calc)->count(2)->create();

        $response = $this->deleteJson('/api/calculators/' . $calc->id);

        $response->assertNoContent();
    }

    public function test_solve_calculator_response(): void
    {
        $calc = Calculator::factory()->recycle($this->barMembership->bar)->create([
            'name' => 'Glass 1',
            'description' => 'Glass 1 Description',
        ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 1',
            'variable_name' => 'input1',
            'value' => '0',
            'sort' => 1,
            'type' => 'input',
        ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 1',
            'variable_name' => 'eval1',
            'value' => 'input1 + 50',
            'sort' => 1,
            'type' => 'eval',
            'settings' => ['prefix' => 'pre', 'suffix' => 'suf'],
        ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 2',
            'variable_name' => 'eval2',
            'value' => 'eval1 * 2',
            'sort' => 2,
            'type' => 'eval',
        ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 3',
            'variable_name' => 'eval3',
            'value' => 'input1 * 0.742',
            'sort' => 3,
            'type' => 'eval',
            'settings' => ['decimal_places' => 2],
        ]);
        CalculatorBlock::factory()->for($calc)->create([
            'label' => 'label 4',
            'variable_name' => 'eval4',
            'value' => 'input1',
            'sort' => 4,
            'type' => 'eval',
            'settings' => null,
        ]);

        $response = $this->postJson('/api/calculators/' . $calc->id . '/solve', [
            'inputs' => [
                'input1' => 250,
            ]
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.inputs.input1', '250')
                ->where('data.results.eval1', 'pre 300 suf')
                ->where('data.results.eval2', '600')
                ->where('data.results.eval3', '185.50')
                ->etc()
        );
    }
}
