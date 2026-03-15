<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Kami\Cocktail\GenAI\DTO\CompleteIngredientRequest;
use Kami\Cocktail\GenAI\DTO\PromptConfiguration;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final readonly class CompleteIngredientHandler
{
    public function __construct(private CompleteIngredientRequest $request)
    {
    }

    public function __invoke(): PromptConfiguration
    {
        $schema = new ObjectSchema(
            name: 'cocktail_ingredient',
            description: 'Basic information about cocktail ingredient',
            properties: [
                new StringSchema('name', 'The name of the ingredient'),
                new StringSchema('description', 'Helpful description of ingredient (1-2 short paragraphs)'),
                new NumberSchema('strength', 'Alcohol by volume percentage', true),
                new StringSchema('color', 'The predominant color of the ingredient as hex value', true),
                new StringSchema('distillery', 'Name of the distillery producing the ingredient', true),
                new StringSchema('origin', 'The geographical origin of the ingredient', true),
            ],
            requiredFields: ['name', 'description', 'color', 'distillery', 'origin', 'strength']
        );

        $prompt = <<<PROMPT
            You are a cocktail and spirits expert. Provide detailed information about the following ingredient used in cocktails.

            Ingredient: {$this->request->ingredientName}

            Instructions:
            - Keep the description informative and concise (1-2 short paragraphs)
            - For strength (ABV), provide the typical alcohol by volume percentage as a number (e.g., 40 for 40% ABV). Use null if non-alcoholic.
            - For color, provide a hex color code (e.g., #FF5733) representing the ingredient's predominant color
            - For distillery, provide the name of a well-known producer if applicable, otherwise use null
            - For origin, specify the country or region where this ingredient typically originates
        PROMPT;

        return new PromptConfiguration(trim($prompt), $schema);
    }
}
