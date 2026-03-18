<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Kami\Cocktail\GenAI\DTO\PromptConfiguration;
use Kami\Cocktail\GenAI\DTO\CompleteIngredientRequest;

final readonly class CompleteIngredientHandler
{
    public function __construct(private CompleteIngredientRequest $request)
    {
    }

    public function __invoke(): PromptConfiguration
    {
        $schema = new ObjectSchema(
            name: 'cocktail_ingredient',
            description: 'Normalized cocktail ingredient profile',
            properties: [
                new StringSchema('name', 'Canonical ingredient name (avoid speculative brand names)'),
                new StringSchema('description', 'Two to four concise factual sentences (non-marketing tone)'),
                new NumberSchema('strength', 'Typical ABV percentage from 0 to 100. Use 0 for explicitly non-alcoholic ingredients, null if unknown or highly variable', true),
                new StringSchema('color', 'Predominant color as #RRGGBB. Use null if colorless, variable, or unknown', true),
                new StringSchema('distillery', 'Representative producer for distilled spirits only. Use null for non-distilled ingredients or uncertain producer', true),
                new StringSchema('origin', 'Typical or historical country/region of origin. Use null if disputed, mixed, or unknown', true),
            ],
            requiredFields: ['name', 'description', 'color', 'distillery', 'origin', 'strength']
        );

        $prompt = <<<PROMPT
            You are a cocktail ingredient reference assistant.
            Return only schema-compatible structured data with no extra keys.

            Ingredient: {$this->request->ingredientName}

            Output rules:
            - Include all top-level keys defined by the schema.
            - Use null only where nullable is allowed.
            - Do not guess. If uncertain or disputed, use null.
            - Prefer category-level facts over brand-specific claims unless universally associated.

            Field rules:
            - name: Use a canonical ingredient name. Avoid speculative brand names.
            - description: Write 2-4 concise factual sentences in a non-marketing tone.
            - strength: Return a number from 0 to 100 representing ABV percentage. Use 0 for explicitly non-alcoholic ingredients, null if unknown or highly variable.
            - color: Use strict #RRGGBB format when known. Use null if colorless, variable, or uncertain.
            - distillery: For distilled spirits, include a representative producer only when confidence is high. Use null for non-distilled ingredients or uncertainty. Never invent producer names.
            - origin: Provide typical or historical country/region only with high confidence. Use null if disputed, mixed, or unknown.
        PROMPT;

        return new PromptConfiguration(trim($prompt), $schema);
    }
}
