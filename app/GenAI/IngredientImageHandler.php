<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Kami\Cocktail\GenAI\DTO\IngredientImageRequest;
use Kami\Cocktail\GenAI\DTO\ImagePromptConfiguration;

final readonly class IngredientImageHandler
{
    public function __construct(private IngredientImageRequest $request)
    {
    }

    public function __invoke(): ImagePromptConfiguration
    {
        $categoryContext = $this->request->category !== null && $this->request->category !== ''
            ? "Category: {$this->request->category}"
            : 'Category: infer the most plausible category from the ingredient name.';
        $parentContext = $this->request->parentIngredientName !== null && $this->request->parentIngredientName !== ''
            ? "Parent ingredient: {$this->request->parentIngredientName}"
            : '';
        $descriptionContext = $this->request->description !== null && $this->request->description !== ''
            ? "Description: {$this->request->description}"
            : '';
        $styleContext = $this->request->style !== null && $this->request->style !== ''
            ? "Visual style: {$this->request->style}"
            : 'Visual style: premium editorial product photography with realistic lighting and accurate colors.';

        $prompt = <<<PROMPT
            Create a single high-quality, photorealistic image of the ingredient described below.

            Subject: {$this->request->ingredientName}
            {$categoryContext}
            {$parentContext}
            {$descriptionContext}
            {$styleContext}

            Background requirements:
            - The image MUST have a fully transparent background (alpha channel), with no backdrop, surface, floor, or background shadow
            - Only the ingredient subject itself should be visible

            Depiction rules:
            - If this ingredient is typically sold in a bottle (spirits, liqueurs, bitters, syrups in bottles, wines, etc.), render it inside the attached reference bottle: a cylindrical clear glass bottle with a natural cork stopper, exactly as shown in the reference image. Fill or partially fill it with liquid whose color, opacity, and character suggest this specific ingredient. Do not add a label, text, branding, or change the bottle shape.
            - If this ingredient is NOT typically sold in a bottle (fresh produce, herbs, garnishes, sugars, spices, etc.), depict the ingredient itself as accurately as possible: its real appearance, texture, and typical form. If it is commonly held in a container, use the most plausible one (small glass jar, bowl, pile, leaves) without any branding or text.
        PROMPT;

        return new ImagePromptConfiguration(trim($prompt));
    }
}
