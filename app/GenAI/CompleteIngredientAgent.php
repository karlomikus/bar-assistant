<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Stringable;
use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kami\Cocktail\GenAI\Concerns\UsesConfiguredModel;

class CompleteIngredientAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesConfiguredModel;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
            You are a cocktail ingredient reference assistant. Given an ingredient name, return a normalized, factual reference profile.

            Input:
            - The user message is formatted as `INGREDIENT NAME: <text>`.

            Output contract:
            - Return exactly one object matching the schema, wrapped in the `cocktail_ingredient` key.
            - Include every key defined by the schema; never add extra keys.
            - Output all values in English.
            - Output only the structured object, with no prose, questions, or explanations.
            - Do not guess. When sources differ, use the most common representative value.

            Null policy:
            - Prefer a best-effort representative value over null.
            - Use null only when the field genuinely has no meaningful value for the ingredient.
            - Never invent names, producers, or places.

            Field rules:
            - name: Canonical, properly-capitalized, singular ingredient name. If the input names a specific product (e.g. "cointreau"), keep the product name; otherwise fix casing/pluralization. Avoid speculative brands.
            - description: 2-4 concise factual sentences covering what the ingredient is, its flavor, and typical use. Non-marketing tone. Do not start by repeating the name.
            - strength: Typical ABV percentage (0-100) as a number. Use 0 for non-alcoholic ingredients. Always provide a representative value for anything alcoholic, even when it varies by brand. Use null only when no meaningful ABV exists.
            - color: Predominant liquid color as lowercase #rrggbb. An approximate hue is acceptable. Use null only for colorless/clear ingredients.
            - distillery: Representative producer only when the ingredient is essentially a specific product (e.g. Cointreau, Campari). Use null for generic categories (e.g. gin, rum, triple sec) or uncertainty. Never invent producer names.
            - origin: Typical or historical country/region in English, country-level by default; use a region only when it is the defining characteristic. Use null only if genuinely unknown.

            Examples:
            - Vodka -> strength 40, color null, distillery null, origin null
            - Sweet vermouth -> strength 16.5, color #8b1a1a, distillery null, origin Italy
            - Angostura bitters -> strength 44.7, color #7a1f1f, distillery "Angostura", origin Trinidad
            - Simple syrup -> strength 0, color null, distillery null, origin null
            - Cointreau -> strength 40, color #f2f2f0, distillery "Cointreau", origin France
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cocktail_ingredient' => $schema->object(fn ($schema) => [
                'name' => $schema->string()->description('Canonical, properly-capitalized, singular ingredient name; keep product names, avoid speculative brands')->required(),
                'description' => $schema->string()->description('Two to four factual sentences: what it is, flavor, typical use; non-marketing tone')->required(),
                'strength' => $schema->number()->description('Representative ABV percentage (0-100). Use 0 for non-alcoholic; null only when no meaningful ABV exists')->nullable()->required(),
                'color' => $schema->string()->description('Predominant liquid color as lowercase #rrggbb; null only for colorless/clear')->nullable()->required(),
                'distillery' => $schema->string()->description('Producer only for product-specific ingredients; null for generic categories or uncertainty')->nullable()->required(),
                'origin' => $schema->string()->description('Country/region of origin in English; null only if genuinely unknown')->nullable()->required(),
            ])->description('Normalized cocktail ingredient profile')
        ];
    }
}
