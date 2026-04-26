<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Kami\Cocktail\GenAI\DTO\CocktailImageRequest;
use Kami\Cocktail\GenAI\DTO\ImagePromptConfiguration;

final readonly class CocktailImageHandler
{
    public function __construct(private CocktailImageRequest $request)
    {
    }

    public function __invoke(): ImagePromptConfiguration
    {
        $glassContext = $this->request->glassName !== null && $this->request->glassName !== ''
            ? "Glassware: {$this->request->glassName}"
            : 'Glassware: infer the most plausible glass from the recipe.';

        $garnishContext = $this->request->garnish !== null && $this->request->garnish !== ''
            ? "Garnish: {$this->request->garnish}"
            : 'Garnish: include one only if the recipe clearly implies it.';

        $styleContext = $this->request->style !== null && $this->request->style !== ''
            ? "Visual style: {$this->request->style}"
            : 'Visual style: premium editorial cocktail photography with realistic lighting and accurate liquid color. Bright soft natural side lighting creates gentle highlights on the glass and a subtle shadow on the table surface.';

        $prompt = <<<PROMPT
            Create a single high-quality, photorealistic image of the cocktail described below.

            Subject: {$this->request->cocktailName}
            {$glassContext}
            {$garnishContext}
            {$styleContext}

            Requirements:
            - Show exactly one finished cocktail as the main subject
            - Match the drink's likely color, opacity, dilution, ice, garnish, and glassware
            - Keep composition clean and product-focused
            - Use a realistic bar or studio setting that supports the drink without distracting from it
            - No people, hands, text overlays, labels, logos, menus, or watermarks
            - Avoid extra drinks, ingredient bottles, or clutter unless subtle and clearly background-only

            Cocktail recipe context:
            {$this->request->cocktailRecipe}
        PROMPT;

        return new ImagePromptConfiguration(trim($prompt));
    }
}
