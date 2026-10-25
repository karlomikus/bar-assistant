<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Stringable;
use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kami\Cocktail\GenAI\Concerns\UsesConfiguredModel;

class CocktailRecipeTextImportAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesConfiguredModel;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
            You are a cocktail recipe extraction assistant. Extract a structured recipe from the input text.

            Input:
            - The user message is formatted as:
              ALLOWED METHODS: <comma-separated list>
              COCKTAIL RECIPE:
              <raw text>

            Output contract:
            - Return exactly one object matching the schema.
            - Include every key defined by the schema; never add extra keys.
            - Output all values in English.
            - Do not guess. Use null for optional fields the source does not support.
            - Preserve ingredient order exactly as written in the source.

            Field rules:
            1) name
            - Cocktail name only, as a short string. Do not append sources, descriptions, or notes.

            2) ingredients (array, preserve source order)
            For each ingredient object output:
            - name: ingredient name, normalized but recognizable
            - amount: required numeric quantity. For a range, use the lower bound (e.g. "1-2 oz" -> 1). For a single value, use that value.
            - amount_max: numeric upper bound only for ranges (e.g. "1-2 oz" -> 2); otherwise null
            - units: normalized unit string (e.g. oz, ml, cl, dash, tsp, tbsp, shot, part, slice, whole). Never empty; use "whole" for countable items stated without a unit (e.g. "1 egg white").
            - note: optional clarification (brand, preparation such as "muddled", or estimate) or null

            Range handling:
            - "1-2 oz" -> amount=1, amount_max=2, units="oz"
            - "2 oz" -> amount=2, amount_max=null

            3) instructions
            - Single string formatted as a numbered markdown list:
              1. ...
              2. ...
            - Concise, imperative steps
            - Do not include ingredient amounts in instructions

            4) method
            - If inferable, set exactly one of the allowed methods (case-insensitive)
            - If the allowed list is empty or no method is inferable, use null

            5) garnish
            - If explicitly stated or clearly implied, output a short string
            - Otherwise null

            6) description
            - One brief paragraph describing history, flavor, or style
            - If missing in source, generate a neutral non-marketing summary

            7) author
            - If the source names a specific person who created or popularized the cocktail, output their name
            - Otherwise null

            8) year
            - Integer year the cocktail was created or first published, only when an explicit single year is stated (e.g. "created in 1919" -> 1919)
            - For decades or approximations (e.g. "1920s", "circa 1800"), or when absent, use null

            9) origin_bar
            - If the source names the bar or venue where the cocktail was created, output it as "Bar Name, City" when the city is known (e.g. "American Bar, London")
            - Otherwise null. Never invent venues
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Cocktail name only')->required(),
            'instructions' => $schema->string()->description('Step by step numbered markdown list to prepare the cocktail')->required(),
            'garnish' => $schema->string()->description('Recommended garnish for the cocktail')->required()->nullable(),
            'author' => $schema->string()->description('Person who created or popularized the cocktail; null if not stated')->nullable(),
            'year' => $schema->integer()->description('Year the cocktail was created or first published; null for decades, approximations, or unknown')->nullable(),
            'origin_bar' => $schema->string()->description('Bar or venue where the cocktail was created, e.g. "American Bar, London"; null if not stated')->nullable(),
            'method' => $schema->string()->description('Cocktail preparation method; must be one of the allowed methods or null')->nullable(),
            'description' => $schema->string()->description('Brief description of the cocktail history, flavor, or style')->required()->nullable(),
            'ingredients' => $schema->array()->description('List of ingredients in source order')->items(
                $schema->object(fn ($schema) => [
                    'name' => $schema->string()->description('Name of the ingredient')->required(),
                    'amount' => $schema->number()->description('Minimum amount (lower bound for ranges)')->required(),
                    'amount_max' => $schema->number()->description('Maximum amount for ranges, otherwise null')->nullable(),
                    'units' => $schema->string()->description('Normalized unit, e.g. oz, ml, dash, whole')->required(),
                    'note' => $schema->string()->description('Additional note about the ingredient')->nullable(),
                ])
            )->required()
        ];
    }
}
