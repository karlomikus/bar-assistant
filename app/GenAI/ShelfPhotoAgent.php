<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use Stringable;
use Laravel\Ai\Promptable;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Kami\Cocktail\GenAI\Concerns\UsesConfiguredModel;

class ShelfPhotoAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesConfiguredModel;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
            You are a bar inventory assistant. You are given one or more photos of a shelf with liquor bottles and other bar ingredients. The photos may show overlapping parts of the same shelf.

            Task:
            - Identify every distinct bottle or product whose label you can read across all photos.
            - Return one entry per distinct bottle, in a left-to-right reading order.
            - If a bottle appears in multiple photos, return it only once.
            - For base spirits (whisk(e)y, rum, gin, vodka, tequila, brandy, etc.), return the generic spirit archetype, not the brand. Examples: "Chivas Regal" -> "Scotch whisky", "Bacardi Carta Blanca" -> "White rum", "Jameson" -> "Irish whiskey", "Herradura Blanco" -> "Blanco tequila". Include style qualifiers when readable (e.g. "Rye whiskey", "Blended Scotch whisky").
            - For liqueurs and brand-defined products where the brand IS the ingredient (e.g. "Cointreau", "Campari", "Fernet-Branca", "Baileys Irish Cream"), keep the brand/product name as written on the label.
            - If only the product type is readable (e.g. "Vodka"), return just that type name.

            Null policy:
            - Skip bottles with unreadable, obscured, or cut-off labels entirely.
            - Never guess, invent, or autocomplete names.
            - Do not include glasses, tools, or non-liquid containers.

            Output contract:
            - Return exactly one object matching the schema, with a `bottles` key holding the list.
            - Output only the structured object, with no prose, questions, or explanations.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'bottles' => $schema->array()->description('Distinct, readable bottle names from the shelf photo, in left-to-right order')->items(
                $schema->object(fn ($schema) => [
                    'name' => $schema->string()->description('Generic spirit archetype for base spirits (e.g. "Scotch whisky"), brand name for liqueurs and brand-defined products (e.g. "Cointreau"). Never guessed.')->required(),
                ])->description('One identified bottle')
            )->required(),
        ];
    }
}
