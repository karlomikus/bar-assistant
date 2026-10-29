<?php

declare(strict_types=1);

namespace Tests\Unit\GenAI;

use Tests\TestCase;
use Kami\Cocktail\GenAI\IngredientImageHandler;
use Kami\Cocktail\GenAI\DTO\IngredientImageRequest;

class IngredientImageHandlerTest extends TestCase
{
    public function testBuildsPromptWithAllIngredientContext(): void
    {
        $handler = new IngredientImageHandler(new IngredientImageRequest(
            ingredientName: 'Plantation 3 Stars Rum',
            category: 'Spirits > Rum',
            description: 'A white Jamaican rum blend.',
            parentIngredientName: 'Rum',
            style: 'editorial studio photo',
        ));

        $prompt = $handler()->prompt;

        $this->assertStringContainsString('Plantation 3 Stars Rum', $prompt);
        $this->assertStringContainsString('Category: Spirits > Rum', $prompt);
        $this->assertStringContainsString('Parent ingredient: Rum', $prompt);
        $this->assertStringContainsString('Description: A white Jamaican rum blend.', $prompt);
        $this->assertStringContainsString('Visual style: editorial studio photo', $prompt);
    }

    public function testPromptRequestsTransparentBackground(): void
    {
        $prompt = (new IngredientImageHandler(new IngredientImageRequest(ingredientName: 'Vodka')))()->prompt;

        $this->assertStringContainsString('transparent background', $prompt);
    }

    public function testPromptContainsBottleBranchInstructions(): void
    {
        $prompt = (new IngredientImageHandler(new IngredientImageRequest(ingredientName: 'Vodka')))()->prompt;

        $this->assertStringContainsString('reference bottle', $prompt);
        $this->assertStringContainsString('cork stopper', $prompt);
        $this->assertStringContainsString('Do not add a label', $prompt);
    }

    public function testPromptContainsNonBottledBranchInstructions(): void
    {
        $prompt = (new IngredientImageHandler(new IngredientImageRequest(ingredientName: 'Lime')))()->prompt;

        $this->assertStringContainsString('NOT typically sold in a bottle', $prompt);
        $this->assertStringContainsString('depict the ingredient itself', $prompt);
    }

    public function testPromptFallsBackToInferredCategory(): void
    {
        $prompt = (new IngredientImageHandler(new IngredientImageRequest(ingredientName: 'Vodka')))()->prompt;

        $this->assertStringContainsString('Category: infer the most plausible category', $prompt);
    }
}
