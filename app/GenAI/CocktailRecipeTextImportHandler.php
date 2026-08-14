<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Kami\Cocktail\GenAI\DTO\PromptConfiguration;
use Kami\Cocktail\GenAI\DTO\CocktailRecipeTextImportRequest;

final readonly class CocktailRecipeTextImportHandler
{
    public function __construct(private CocktailRecipeTextImportRequest $request)
    {
    }

    public function __invoke(): PromptConfiguration
    {
        $schema = new ObjectSchema(
            name: 'cocktail_recipe',
            description: 'Cocktail recipe parsed by LLM',
            properties: [
                new StringSchema('name', 'Title of the recipe'),
                new StringSchema('instructions', 'Step by step instructions to prepare the cocktail'),
                new StringSchema('garnish', 'Recommended garnish for the cocktail', true),
                new StringSchema('author', 'Historical author or creator of the cocktail recipe', true),
                new StringSchema('method', 'Cocktail preperation method.', true),
                new StringSchema('description', 'Helpful description of the cocktail (1-2 short paragraphs)', true),
                new ArraySchema('ingredients', 'List of ingredients', new ObjectSchema(
                    name: 'cocktail_recipe_ingredient',
                    description: 'Cocktail ingredient',
                    properties: [
                        new StringSchema('name', 'Name of the ingredient'),
                        new NumberSchema('amount', 'The min amount of the ingredient'),
                        new NumberSchema('amount_max', 'The max amount of the ingredient', true),
                        new StringSchema('units', 'Units of the ingredient amount'),
                        new StringSchema('note', 'Additional note about the ingredient', true),
                    ],
                    requiredFields: ['name', 'amount', 'units']
                )),
            ],
            requiredFields: ['name', 'instructions', 'ingredients', 'description', 'garnish']
        );

        $allowedMethods = implode('|', $this->request->allowedMethods);

        $prompt = <<<PROMPT
            Extract the cocktail recipe from the input text into the provided schema.

            Output requirements:
            - Return only schema-compatible structured data
            - Include all top-level keys expected by schema
            - Use null only where schema allows nullable values

            Field rules:
            1) name
            - Cocktail name as a short string

            2) ingredients (array, preserve source order)
            For each ingredient object, output:
            - name: ingredient name, normalized but recognizable
            - amount_max: only for ranges, otherwise null
            - units: normalized unit string
            - note: optional clarification (brand, preparation, estimate) or null

            Range handling:
            - "1-2 oz" => amount=1, amount_max=2, units="oz"
            - "2 oz" => amount=2, amount_max=null

            3) instructions
            - Single string formatted as a numbered markdown list:
              1. ...
              2. ...
            - Keep concise, imperative steps
            - Do not include ingredient amounts in instructions

            4) method
            - If inferable, set exactly one of: {$allowedMethods}
            - If not inferable, set null

            5) garnish
            - If explicitly stated or clearly implied, output string
            - Otherwise null

            6) description
            - One brief paragraph describing history, flavor or style
            - If missing in source, generate a neutral non-marketing summary

            7) author
            - If the source mentions a specific person who created or popularized the cocktail, output their name
            - Otherwise null

            COCKTAIL RECIPE:
            {$this->request->textRecipe}
        PROMPT;

        return new PromptConfiguration(trim($prompt), $schema);
    }
}
